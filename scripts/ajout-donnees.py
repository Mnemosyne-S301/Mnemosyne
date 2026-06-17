#!/usr/bin/env python3


from __future__ import annotations

import hashlib
import json
import math
import os
import random
import re
import statistics
import unicodedata
from collections import Counter, defaultdict
from dataclasses import asdict, dataclass, field
from datetime import date, datetime
from typing import Any, Iterable, Mapping, Sequence

import mysql.connector
from mysql.connector import Error


# ============================================================
# CONFIGURATION
# ============================================================


def env_bool(name: str, default: bool) -> bool:
    raw = os.getenv(name)
    if raw is None:
        return default
    return raw.strip().lower() in {"1", "true", "yes", "oui", "on"}


def env_int(name: str, default: int, minimum: int | None = None) -> int:
    raw = os.getenv(name)
    value = default if raw is None else int(raw)
    if minimum is not None and value < minimum:
        raise ValueError(f"{name} doit être supérieur ou égal à {minimum}.")
    return value


def env_float(
    name: str,
    default: float,
    minimum: float | None = None,
    maximum: float | None = None,
) -> float:
    raw = os.getenv(name)
    value = default if raw is None else float(raw)
    if minimum is not None and value < minimum:
        raise ValueError(f"{name} doit être supérieur ou égal à {minimum}.")
    if maximum is not None and value > maximum:
        raise ValueError(f"{name} doit être inférieur ou égal à {maximum}.")
    return value


def env_json(name: str, default: Any) -> Any:
    raw = os.getenv(name)
    if raw is None or raw.strip() == "":
        return default
    try:
        return json.loads(raw)
    except json.JSONDecodeError as exc:
        raise ValueError(f"{name} ne contient pas un JSON valide : {exc}") from exc


DB_CONFIG = {
    "host": os.getenv("DB_HOST", "mnemosyne-mysql"),
    "port": env_int("DB_PORT", 3306, 1),
    "user": os.getenv("DB_USER", "phpserv"),
    "password": os.getenv("DB_PASSWORD", "phpserv"),
    "database": os.getenv("DB_NAME", "scolarite"),
    "connection_timeout": env_int("DB_TIMEOUT", 15, 1),
}

DRY_RUN = env_bool("DRY_RUN", True)
FILL_MODE = os.getenv("FILL_MODE", "empty").strip().lower()
if FILL_MODE not in {"empty", "partial"}:
    raise ValueError("FILL_MODE doit valoir 'empty' ou 'partial'.")

MIN_CONFIDENCE = env_float("MIN_CONFIDENCE", 0.55, 0.0, 1.0)
ALLOW_SYNTHETIC = env_bool("ALLOW_SYNTHETIC", True)
MAX_SYNTHETIC_RATIO = env_float("MAX_SYNTHETIC_RATIO", 1.0, 0.0, 1.0)
MAX_INSERTIONS = env_int("MAX_INSERTIONS", 5000, 1)
MAX_TARGET_PER_CELL = env_int("MAX_TARGET_PER_CELL", 250, 1)
MIN_TARGET_PER_CELL = env_int("MIN_TARGET_PER_CELL", 5, 1)
REBUILD_GENERATED = env_bool("REBUILD_GENERATED", False)
USE_REFERENCE_CAPS = env_bool("USE_REFERENCE_CAPS", False)
BRIDGE_EMPTY_YEARS = env_bool("BRIDGE_EMPTY_YEARS", True)
LOCK_TIMEOUT = env_int("LOCK_TIMEOUT", 8, 0)
REPORT_LIMIT = env_int("REPORT_LIMIT", 250, 1)
SEED = env_int("SEED", 301)

TARGET_OVERRIDES_RAW = env_json("TARGET_OVERRIDES_JSON", {})
FORCE_ACTIVE_RAW = env_json("FORCE_ACTIVE_JSON", [])

GENERATED_PREFIX = os.getenv("GENERATED_PREFIX", "SYNTH_V4_")
GENERATED_STATE_PREFIX = os.getenv("GENERATED_STATE_PREFIX", "SYNTHETIQUE_V4")

# Ces valeurs ne sont utilisées que si USE_REFERENCE_CAPS=1.
# Elles servent de plafond/repère pour le total BUT1 d'une spécialité,
# jamais de nombre imposé par parcours ou par version technique.
REFERENCE_BUT1_CAPS = {
    "INFO": 104,
    "RT": 74,
    "SD": 56,
    "GEII": 100,
    "GEA": 140,
    "CJ": 150,
}

PASS_CODES = {"ADM", "ADJ", "PASD", "PAS1NCI"}
REPEAT_CODES = {"RED"}
EXIT_CODES = {"NAR", "DEM", "ABL", "ABAN", "DEF"}

CODE_MEANINGS = {
    "ADM": "Admis",
    "ADJ": "Admis par décision du jury",
    "PASD": "Passage de droit",
    "PAS1NCI": "Passage sous condition",
    "RED": "Redoublement",
    "NAR": "Non admis à redoubler",
    "DEM": "Démission",
    "ABL": "Abandon",
    "ABAN": "Abandon",
    "DEF": "Défaillant",
}


# ============================================================
# MODÈLES
# ============================================================


@dataclass(frozen=True, order=True)
class ProgramKey:
    specialty: str
    modality: str
    parcours: str
    order: int

    @property
    def root(self) -> tuple[str, str]:
        return (self.specialty, self.modality)

    @property
    def label(self) -> str:
        return f"{self.specialty}|{self.modality}|{self.parcours}|BUT{self.order}"

    def with_order(self, order: int) -> "ProgramKey":
        return ProgramKey(
            specialty=self.specialty,
            modality=self.modality,
            parcours=self.parcours,
            order=order,
        )


@dataclass
class Structure:
    formation_id: int
    acronym: str
    version: int
    parcours_id: int
    parcours_code: str
    anneeformation_id: int
    order: int
    key: ProgramKey
    usage_total: int = 0
    semester_years: set[int] = field(default_factory=set)
    data_years: set[int] = field(default_factory=set)


@dataclass(frozen=True)
class AnnualRecord:
    year: int
    student_id: int
    key: ProgramKey
    code: str
    anneeformation_id: int
    synthetic: bool = False


@dataclass
class TargetEstimate:
    target: int
    confidence: float
    method: str
    evidence: list[str]


@dataclass
class CellPlan:
    year: int
    key: ProgramKey
    structure: Structure
    current: int
    estimate: TargetEstimate
    requested: int
    status: str = "planned"
    real_candidates: int = 0
    inserted_real: int = 0
    inserted_synthetic: int = 0
    inserted_total: int = 0
    decisions: dict[str, int] = field(default_factory=dict)
    notes: list[str] = field(default_factory=list)

    def report(self) -> dict[str, Any]:
        return {
            "annee": self.year,
            "programme": self.key.label,
            "formation_id": self.structure.formation_id,
            "anneeformation_id": self.structure.anneeformation_id,
            "effectif_avant": self.current,
            "objectif": self.estimate.target,
            "a_ajouter": self.requested,
            "confiance": round(self.estimate.confidence, 3),
            "methode": self.estimate.method,
            "preuves": self.estimate.evidence,
            "statut": self.status,
            "candidats_reels": self.real_candidates,
            "inseres_reels": self.inserted_real,
            "inseres_synthetiques": self.inserted_synthetic,
            "inseres_total": self.inserted_total,
            "decisions": self.decisions,
            "notes": self.notes,
        }


# ============================================================
# NORMALISATION ET PARSING
# ============================================================


def normalize_text(value: str | None) -> str:
    text = unicodedata.normalize("NFKD", value or "")
    text = text.encode("ascii", "ignore").decode("ascii")
    text = text.upper().replace("&AMP;", "&")
    return re.sub(r"\s+", " ", text).strip()


def normalize_token(value: str | None, fallback: str = "STD") -> str:
    text = normalize_text(value)
    text = re.sub(r"[^A-Z0-9&]+", "_", text).strip("_")
    return text or fallback


def detect_specialty(acronym: str | None) -> str | None:
    raw = (acronym or "").upper().replace("&AMP;", "&")
    text = normalize_text(acronym)

    if not text.startswith("BUT"):
        return None
    if "GEII" in text:
        return "GEII"
    if "GEA" in text:
        return "GEA"
    if "INFO" in text or "INFORMATIQUE" in text:
        return "INFO"
    if (
        "R&T" in raw
        or "RESEAUX" in text
        or "TELECOMMUNICATION" in text
        or re.search(r"\bR\s*&\s*T\b", raw)
    ):
        return "RT"
    if text.startswith("BUT SD") or "SCIENCE DES DONNEES" in text:
        return "SD"
    if text.startswith("BUT CJ") or "CARRIERES JURIDIQUES" in text:
        return "CJ"
    return None


def detect_modality(acronym: str | None) -> str:
    text = normalize_text(acronym)
    tokens = set(re.findall(r"[A-Z0-9]+", text))

    if {"APP", "APPRENTISSAGE", "ALT", "ALTERNANCE"} & tokens:
        return "APP"
    if "FA" in tokens:
        return "FA"
    if "FC" in tokens:
        return "FC"
    if "FI" in tokens:
        return "FI"
    return "STD"


def academic_year(value: Any) -> int | None:
    if value is None:
        return None
    if isinstance(value, datetime):
        parsed = value.date()
    elif isinstance(value, date):
        parsed = value
    else:
        try:
            parsed = datetime.fromisoformat(str(value)).date()
        except (TypeError, ValueError):
            return None
    return parsed.year if parsed.month >= 8 else parsed.year - 1


def parse_program_key(value: str) -> tuple[int, ProgramKey]:
    parts = [part.strip() for part in value.split("|")]
    if len(parts) != 5:
        raise ValueError(
            "Une clé doit respecter ANNEE|SPECIALITE|MODALITE|PARCOURS|ORDRE : "
            f"{value}"
        )
    year = int(parts[0])
    key = ProgramKey(
        specialty=normalize_token(parts[1]),
        modality=normalize_token(parts[2]),
        parcours=normalize_token(parts[3]),
        order=int(parts[4]),
    )
    if key.order not in {1, 2, 3}:
        raise ValueError(f"Ordre invalide dans {value}")
    return year, key


def parse_overrides(raw: Mapping[str, Any]) -> dict[tuple[int, ProgramKey], int]:
    result: dict[tuple[int, ProgramKey], int] = {}
    for raw_key, raw_value in raw.items():
        year, key = parse_program_key(str(raw_key))
        target = int(raw_value)
        if target < 0:
            raise ValueError(f"Objectif négatif pour {raw_key}")
        result[(year, key)] = target
    return result


def parse_forced(raw: Sequence[Any]) -> set[tuple[int, ProgramKey]]:
    return {parse_program_key(str(value)) for value in raw}


TARGET_OVERRIDES = parse_overrides(TARGET_OVERRIDES_RAW)
FORCE_ACTIVE = parse_forced(FORCE_ACTIVE_RAW)


# ============================================================
# STATISTIQUES ROBUSTES
# ============================================================


def clamp(value: float, minimum: float, maximum: float) -> float:
    return max(minimum, min(maximum, value))


def robust_values(values: Iterable[int]) -> list[int]:
    clean = sorted(int(value) for value in values if int(value) > 0)
    if len(clean) < 4:
        return clean

    median = statistics.median(clean)
    deviations = [abs(value - median) for value in clean]
    mad = statistics.median(deviations)
    if mad == 0:
        return clean

    lower = median - 3.5 * mad
    upper = median + 3.5 * mad
    filtered = [value for value in clean if lower <= value <= upper]
    return filtered or clean


def weighted_median(items: Sequence[tuple[float, float]]) -> float:
    valid = sorted(
        (float(value), float(weight))
        for value, weight in items
        if weight > 0 and value >= 0
    )
    if not valid:
        raise ValueError("Impossible de calculer une médiane pondérée vide.")

    total = sum(weight for _, weight in valid)
    threshold = total / 2
    cumulative = 0.0
    for value, weight in valid:
        cumulative += weight
        if cumulative >= threshold:
            return value
    return valid[-1][0]


def stable_hash(*parts: Any) -> int:
    payload = "|".join(str(part) for part in parts).encode("utf-8")
    return int(hashlib.sha256(payload).hexdigest()[:16], 16)


def deterministic_rng(*parts: Any) -> random.Random:
    return random.Random(SEED ^ stable_hash(*parts))


# ============================================================
# ACCÈS ET VALIDATION DU SCHÉMA
# ============================================================


REQUIRED_COLUMNS = {
    "Formation": {
        "formation_id", "accronyme", "version",
    },
    "Parcours": {
        "parcours_id", "code", "formation_id",
    },
    "AnneeFormation": {
        "anneeformation_id", "ordre", "parcours_id",
    },
    "FormSemestre": {
        "formsemestre_id", "date_debut", "date_fin", "anneeformation_id",
    },
    "Etudiant": {
        "etudiant_id", "code_nip", "etat",
    },
    "CodeAnnee": {
        "codeannee_id", "code", "signification",
    },
    "EffectuerAnnee": {
        "annee_scolaire", "anneeformation_id", "etudiant_id", "codeannee_id",
    },
}


def validate_schema(cursor: Any) -> None:
    for table, expected_columns in REQUIRED_COLUMNS.items():
        cursor.execute(
            """
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = %s
              AND TABLE_NAME = %s
            """,
            (DB_CONFIG["database"], table),
        )
        actual = {str(row[0]) for row in cursor.fetchall()}
        missing = expected_columns - actual
        if missing:
            raise RuntimeError(
                f"Colonnes manquantes dans {table} : {', '.join(sorted(missing))}"
            )


def acquire_lock(cursor: Any) -> None:
    lock_name = f"mnemosyne_smart_fill_{DB_CONFIG['database']}"
    cursor.execute("SELECT GET_LOCK(%s, %s)", (lock_name, LOCK_TIMEOUT))
    row = cursor.fetchone()
    if not row or int(row[0] or 0) != 1:
        raise RuntimeError("Une autre synchronisation est déjà en cours.")


def release_lock(cursor: Any) -> None:
    lock_name = f"mnemosyne_smart_fill_{DB_CONFIG['database']}"
    try:
        cursor.execute("SELECT RELEASE_LOCK(%s)", (lock_name,))
        cursor.fetchone()
    except Exception:
        pass


# ============================================================
# CHARGEMENT DES STRUCTURES ET DONNÉES
# ============================================================


def make_program_key(acronym: str | None, parcours: str | None, order: int) -> ProgramKey | None:
    specialty = detect_specialty(acronym)
    if specialty is None or int(order) not in {1, 2, 3}:
        return None
    return ProgramKey(
        specialty=specialty,
        modality=detect_modality(acronym),
        parcours=normalize_token(parcours),
        order=int(order),
    )


def load_structures(cursor: Any) -> tuple[
    dict[int, Structure],
    dict[ProgramKey, list[Structure]],
]:
    cursor.execute(
        """
        SELECT
            f.formation_id,
            f.accronyme,
            COALESCE(f.version, 0),
            p.parcours_id,
            p.code,
            af.anneeformation_id,
            af.ordre,
            COUNT(DISTINCT ea.etudiant_id) AS usage_total
        FROM Formation f
        JOIN Parcours p
          ON p.formation_id = f.formation_id
        JOIN AnneeFormation af
          ON af.parcours_id = p.parcours_id
        LEFT JOIN EffectuerAnnee ea
          ON ea.anneeformation_id = af.anneeformation_id
        WHERE af.ordre IN (1, 2, 3)
        GROUP BY
            f.formation_id,
            f.accronyme,
            f.version,
            p.parcours_id,
            p.code,
            af.anneeformation_id,
            af.ordre
        """
    )

    by_id: dict[int, Structure] = {}
    by_key: dict[ProgramKey, list[Structure]] = defaultdict(list)

    for row in cursor.fetchall():
        (
            formation_id,
            acronym,
            version,
            parcours_id,
            parcours_code,
            anneeformation_id,
            order,
            usage_total,
        ) = row

        key = make_program_key(acronym, parcours_code, int(order))
        if key is None:
            continue

        structure = Structure(
            formation_id=int(formation_id),
            acronym=str(acronym or ""),
            version=int(version or 0),
            parcours_id=int(parcours_id),
            parcours_code=normalize_token(parcours_code),
            anneeformation_id=int(anneeformation_id),
            order=int(order),
            key=key,
            usage_total=int(usage_total or 0),
        )
        by_id[structure.anneeformation_id] = structure
        by_key[key].append(structure)

    cursor.execute(
        """
        SELECT anneeformation_id, date_debut, date_fin
        FROM FormSemestre
        """
    )
    for anneeformation_id, start_date, end_date in cursor.fetchall():
        structure = by_id.get(int(anneeformation_id))
        if structure is None:
            continue
        for raw_date in (start_date, end_date):
            year = academic_year(raw_date)
            if year is not None:
                structure.semester_years.add(year)

    cursor.execute(
        """
        SELECT DISTINCT anneeformation_id, annee_scolaire
        FROM EffectuerAnnee
        """
    )
    for anneeformation_id, year in cursor.fetchall():
        structure = by_id.get(int(anneeformation_id))
        if structure is not None:
            structure.data_years.add(int(year))

    if not by_id:
        raise RuntimeError("Aucune structure BUT exploitable n'a été trouvée.")

    return by_id, by_key


class DataState:
    def __init__(self) -> None:
        self.records: list[AnnualRecord] = []
        self.cell_students: dict[tuple[int, ProgramKey], set[int]] = defaultdict(set)
        self.year_students: dict[int, set[int]] = defaultdict(set)
        self.student_year_records: dict[tuple[int, int], list[AnnualRecord]] = defaultdict(list)
        self.root_order_students: dict[tuple[int, tuple[str, str], int], set[int]] = defaultdict(set)
        self.code_exact: dict[tuple[ProgramKey, str], Counter[str]] = defaultdict(Counter)
        self.code_root: dict[tuple[tuple[str, str], int, str], Counter[str]] = defaultdict(Counter)
        self.code_order: dict[tuple[int, str], Counter[str]] = defaultdict(Counter)

    def add_record(self, record: AnnualRecord) -> None:
        self.records.append(record)
        self.cell_students[(record.year, record.key)].add(record.student_id)
        self.year_students[record.year].add(record.student_id)
        self.student_year_records[(record.year, record.student_id)].append(record)
        self.root_order_students[(record.year, record.key.root, record.key.order)].add(
            record.student_id
        )

        category = code_category(record.code)
        if category != "other":
            self.code_exact[(record.key, category)][record.code] += 1
            self.code_root[(record.key.root, record.key.order, category)][record.code] += 1
            self.code_order[(record.key.order, category)][record.code] += 1

    def cell_count(self, year: int, key: ProgramKey) -> int:
        return len(self.cell_students.get((year, key), set()))


def load_state(cursor: Any, structures_by_id: Mapping[int, Structure]) -> DataState:
    cursor.execute(
        """
        SELECT
            ea.annee_scolaire,
            ea.anneeformation_id,
            ea.etudiant_id,
            ca.code,
            e.code_nip,
            e.etat
        FROM EffectuerAnnee ea
        JOIN CodeAnnee ca
          ON ca.codeannee_id = ea.codeannee_id
        JOIN Etudiant e
          ON e.etudiant_id = ea.etudiant_id
        """
    )

    state = DataState()
    for year, anneeformation_id, student_id, code, code_nip, etat in cursor.fetchall():
        structure = structures_by_id.get(int(anneeformation_id))
        if structure is None:
            continue
        synthetic = (
            str(code_nip or "").startswith(GENERATED_PREFIX)
            or str(etat or "").startswith("SYNTHETIQUE")
        )
        state.add_record(
            AnnualRecord(
                year=int(year),
                student_id=int(student_id),
                key=structure.key,
                code=str(code or ""),
                anneeformation_id=int(anneeformation_id),
                synthetic=synthetic,
            )
        )
    return state


def determine_years(cursor: Any) -> list[int]:
    raw = os.getenv("YEARS", "").strip()
    if raw:
        years = sorted({int(value.strip()) for value in raw.split(",") if value.strip()})
        if not years:
            raise ValueError("YEARS ne contient aucune année valide.")
        return years

    cursor.execute(
        """
        SELECT MIN(annee_scolaire), MAX(annee_scolaire)
        FROM EffectuerAnnee
        """
    )
    row = cursor.fetchone()
    if not row or row[0] is None or row[1] is None:
        raise RuntimeError("La base ne contient aucune année scolaire.")
    return list(range(int(row[0]), int(row[1]) + 1))


# ============================================================
# NETTOYAGE OPTIONNEL DES DONNÉES GÉNÉRÉES
# ============================================================


def rebuild_generated_rows(cursor: Any, years: Sequence[int]) -> dict[str, int]:
    if not REBUILD_GENERATED:
        return {"lignes_supprimees": 0, "etudiants_supprimes": 0}

    placeholders = ",".join(["%s"] * len(years))
    params: list[Any] = list(years)
    params.extend([GENERATED_PREFIX + "%", "SYNTHETIQUE%"])

    cursor.execute(
        f"""
        DELETE ea
        FROM EffectuerAnnee ea
        JOIN Etudiant e
          ON e.etudiant_id = ea.etudiant_id
        WHERE ea.annee_scolaire IN ({placeholders})
          AND (e.code_nip LIKE %s OR e.etat LIKE %s)
        """,
        tuple(params),
    )
    deleted_rows = int(cursor.rowcount)

    cursor.execute(
        """
        DELETE e
        FROM Etudiant e
        WHERE (e.code_nip LIKE %s OR e.etat LIKE %s)
          AND NOT EXISTS (
              SELECT 1 FROM EffectuerAnnee ea
              WHERE ea.etudiant_id = e.etudiant_id
          )
          AND NOT EXISTS (
              SELECT 1 FROM EffectuerUE eu
              WHERE eu.etudiant_id = e.etudiant_id
          )
          AND NOT EXISTS (
              SELECT 1 FROM EffectuerRCUE er
              WHERE er.etudiant_id = e.etudiant_id
          )
        """,
        (GENERATED_PREFIX + "%", "SYNTHETIQUE%"),
    )
    deleted_students = int(cursor.rowcount)

    return {
        "lignes_supprimees": deleted_rows,
        "etudiants_supprimes": deleted_students,
    }


# ============================================================
# ACTIVITÉ ET STRUCTURE CANONIQUE
# ============================================================


def aggregate_years(structures: Sequence[Structure], attribute: str) -> set[int]:
    result: set[int] = set()
    for structure in structures:
        result.update(getattr(structure, attribute))
    return result


def is_key_active(
    year: int,
    key: ProgramKey,
    structures: Sequence[Structure],
) -> tuple[bool, list[str]]:
    if (year, key) in FORCE_ACTIVE:
        return True, ["activation_forcée"]

    semester_years = aggregate_years(structures, "semester_years")
    data_years = aggregate_years(structures, "data_years")
    evidence: list[str] = []

    if year in semester_years:
        evidence.append("semestre_exact")
    if year in data_years:
        evidence.append("données_exactes")
    if BRIDGE_EMPTY_YEARS:
        if year - 1 in data_years and year + 1 in data_years:
            evidence.append("pont_données_années_voisines")
        if year - 1 in semester_years and year + 1 in semester_years:
            evidence.append("pont_semestres_années_voisines")

    return bool(evidence), evidence


def choose_canonical_structure(
    year: int,
    structures: Sequence[Structure],
) -> Structure:
    def distance(values: set[int]) -> int:
        return min((abs(year - value) for value in values), default=999)

    def score(structure: Structure) -> tuple[int, ...]:
        return (
            1 if year in structure.semester_years else 0,
            1 if year in structure.data_years else 0,
            1 if year - 1 in structure.data_years else 0,
            1 if year + 1 in structure.data_years else 0,
            -distance(structure.semester_years),
            -distance(structure.data_years),
            structure.usage_total,
            structure.version,
            -structure.formation_id,
        )

    return max(structures, key=score)


# ============================================================
# ESTIMATION DES EFFECTIFS
# ============================================================


def nonzero_history(state: DataState, key: ProgramKey, excluded_year: int) -> dict[int, int]:
    result: dict[int, int] = {}
    years = {year for year, current_key in state.cell_students if current_key == key}
    for year in years:
        if year == excluded_year:
            continue
        count = state.cell_count(year, key)
        if count > 0:
            result[year] = count
    return result


def sibling_counts(state: DataState, key: ProgramKey, excluded_year: int) -> list[int]:
    values: list[int] = []
    for (year, current_key), students in state.cell_students.items():
        if year == excluded_year:
            continue
        if (
            current_key.root == key.root
            and current_key.order == key.order
            and current_key.parcours != key.parcours
            and students
        ):
            values.append(len(students))
    return robust_values(values)


def transition_rate(
    state: DataState,
    root: tuple[str, str],
    from_order: int,
) -> tuple[float, float, list[str]]:
    rates: list[float] = []
    all_years = sorted({year for year, _, _ in state.root_order_students})

    for year in all_years:
        source = state.root_order_students.get((year, root, from_order), set())
        destination = state.root_order_students.get(
            (year + 1, root, from_order + 1), set()
        )
        if len(source) < 8 or not destination:
            continue
        rate = len(source & destination) / len(source)
        if 0.05 <= rate <= 1.0:
            rates.append(rate)

    filtered = [rate for rate in rates if 0.15 <= rate <= 0.95]
    if filtered:
        value = float(statistics.median(filtered))
        confidence = min(0.88, 0.55 + 0.08 * len(filtered))
        return value, confidence, [
            f"taux_transition_médian={value:.3f}",
            f"observations_transition={len(filtered)}",
        ]

    fallback = 0.65 if from_order == 1 else 0.75
    return fallback, 0.35, [f"taux_transition_secours={fallback:.2f}"]


def estimate_target(
    year: int,
    key: ProgramKey,
    state: DataState,
    active_evidence: Sequence[str],
) -> TargetEstimate:
    override = TARGET_OVERRIDES.get((year, key))
    if override is not None:
        return TargetEstimate(
            target=min(MAX_TARGET_PER_CELL, max(0, override)),
            confidence=1.0,
            method="override",
            evidence=["objectif_explicitement_fourni", *active_evidence],
        )

    history = nonzero_history(state, key, year)
    robust_history = robust_values(history.values())
    prev_count = history.get(year - 1)
    next_count = history.get(year + 1)

    candidates: list[tuple[float, float]] = []
    evidence = list(active_evidence)
    confidence_parts: list[float] = []
    methods: list[str] = []

    if prev_count is not None and next_count is not None:
        interpolation = (prev_count + next_count) / 2
        candidates.append((interpolation, 5.0))
        evidence.append(f"interpolation={prev_count}/{next_count}")
        confidence_parts.append(0.95)
        methods.append("interpolation")
    elif prev_count is not None:
        candidates.append((prev_count, 2.5))
        evidence.append(f"année_précédente={prev_count}")
        confidence_parts.append(0.72)
        methods.append("année_précédente")
    elif next_count is not None:
        candidates.append((next_count, 2.5))
        evidence.append(f"année_suivante={next_count}")
        confidence_parts.append(0.72)
        methods.append("année_suivante")

    if robust_history:
        history_median = float(statistics.median(robust_history))
        candidates.append((history_median, 3.0))
        evidence.append(
            f"médiane_historique={history_median:.1f} sur {len(robust_history)} année(s)"
        )
        confidence_parts.append(min(0.88, 0.58 + 0.07 * len(robust_history)))
        methods.append("historique")

    # Estimation par cohorte pour les niveaux 2 et 3.
    if key.order > 1:
        source_count = len(
            state.root_order_students.get((year - 1, key.root, key.order - 1), set())
        )
        if source_count > 0:
            rate, rate_confidence, rate_evidence = transition_rate(
                state, key.root, key.order - 1
            )
            transition_estimate = source_count * rate
            candidates.append((transition_estimate, 3.5))
            evidence.extend(
                [f"cohorte_source={source_count}", *rate_evidence]
            )
            confidence_parts.append(rate_confidence)
            methods.append("transition")

    # Rétro-estimation du BUT1 à partir du BUT2 suivant.
    if key.order == 1:
        next_second_year = len(
            state.root_order_students.get((year + 1, key.root, 2), set())
        )
        if next_second_year > 0:
            rate, rate_confidence, rate_evidence = transition_rate(state, key.root, 1)
            if rate > 0:
                back_estimate = next_second_year / rate
                candidates.append((back_estimate, 3.0))
                evidence.extend(
                    [f"BUT2_année_suivante={next_second_year}", *rate_evidence]
                )
                confidence_parts.append(max(0.45, rate_confidence - 0.08))
                methods.append("rétro_transition")

    siblings = sibling_counts(state, key, year)
    if siblings:
        sibling_median = float(statistics.median(siblings))
        candidates.append((sibling_median, 1.2))
        evidence.append(f"médiane_parcours_frères={sibling_median:.1f}")
        confidence_parts.append(0.48)
        methods.append("parcours_frères")

    if not candidates:
        return TargetEstimate(
            target=0,
            confidence=0.0,
            method="aucune_estimation",
            evidence=[*active_evidence, "aucune_donnée_quantitative_exploitable"],
        )

    target = int(round(weighted_median(candidates)))
    target = max(MIN_TARGET_PER_CELL, min(MAX_TARGET_PER_CELL, target))

    # Empêche une extrapolation nettement supérieure à l'historique réel.
    if robust_history:
        historical_ceiling = max(robust_history)
        target = min(target, max(MIN_TARGET_PER_CELL, int(math.ceil(historical_ceiling * 1.20))))

    confidence = max(confidence_parts, default=0.0)
    if "semestre_exact" in active_evidence:
        confidence = min(1.0, confidence + 0.04)
    if "pont_données_années_voisines" in active_evidence:
        confidence = min(1.0, confidence + 0.03)

    method = "+".join(dict.fromkeys(methods))
    return TargetEstimate(
        target=target,
        confidence=confidence,
        method=method,
        evidence=evidence,
    )


def apply_reference_caps(plans: list[CellPlan]) -> None:
    if not USE_REFERENCE_CAPS:
        return

    grouped: dict[tuple[int, str], list[CellPlan]] = defaultdict(list)
    for plan in plans:
        if plan.key.order == 1 and plan.key.specialty in REFERENCE_BUT1_CAPS:
            grouped[(plan.year, plan.key.specialty)].append(plan)

    for (_, specialty), specialty_plans in grouped.items():
        cap = REFERENCE_BUT1_CAPS[specialty]
        total_target = sum(plan.estimate.target for plan in specialty_plans)
        if total_target <= cap or total_target == 0:
            continue

        remaining = cap
        sorted_plans = sorted(
            specialty_plans,
            key=lambda plan: (-plan.current, -plan.estimate.confidence, plan.key.label),
        )
        weight_sum = sum(max(1, plan.estimate.target) for plan in sorted_plans)
        allocations: dict[str, int] = {}

        for index, plan in enumerate(sorted_plans):
            if index == len(sorted_plans) - 1:
                allocation = remaining
            else:
                allocation = round(cap * max(1, plan.estimate.target) / weight_sum)
                allocation = min(allocation, remaining)
            allocation = max(plan.current, allocation)
            allocations[plan.key.label] = allocation
            remaining -= allocation

        for plan in sorted_plans:
            capped = allocations[plan.key.label]
            if capped < plan.estimate.target:
                plan.estimate.evidence.append(
                    f"plafond_référence_{specialty}={cap}"
                )
                plan.estimate.target = capped
                plan.requested = max(0, capped - plan.current)


# ============================================================
# PLANIFICATION
# ============================================================


def build_plan(
    years: Sequence[int],
    structures_by_key: Mapping[ProgramKey, Sequence[Structure]],
    state: DataState,
) -> tuple[list[CellPlan], list[dict[str, Any]]]:
    plans: list[CellPlan] = []
    skipped: list[dict[str, Any]] = []

    for year in years:
        for key in sorted(structures_by_key):
            structures = structures_by_key[key]
            active, active_evidence = is_key_active(year, key, structures)
            if not active:
                continue

            structure = choose_canonical_structure(year, structures)
            current = state.cell_count(year, key)
            estimate = estimate_target(year, key, state, active_evidence)

            if FILL_MODE == "empty" and current > 0:
                continue

            requested = max(0, estimate.target - current)
            if requested <= 0:
                continue

            if estimate.confidence < MIN_CONFIDENCE:
                skipped.append({
                    "annee": year,
                    "programme": key.label,
                    "raison": "confiance_insuffisante",
                    "confiance": round(estimate.confidence, 3),
                    "objectif_estimé": estimate.target,
                    "preuves": estimate.evidence,
                })
                continue

            plans.append(
                CellPlan(
                    year=year,
                    key=key,
                    structure=structure,
                    current=current,
                    estimate=estimate,
                    requested=requested,
                )
            )

    apply_reference_caps(plans)
    plans = [plan for plan in plans if plan.requested > 0]
    plans.sort(key=lambda plan: (plan.year, plan.key.order, plan.key.label))
    return plans, skipped


# ============================================================
# CANDIDATS RÉELS ET DÉCISIONS
# ============================================================


def code_category(code: str) -> str:
    if code in PASS_CODES:
        return "pass"
    if code in REPEAT_CODES:
        return "repeat"
    if code in EXIT_CODES:
        return "exit"
    return "other"


def records_matching_root(
    records: Iterable[AnnualRecord],
    root: tuple[str, str],
    order: int,
) -> list[AnnualRecord]:
    return [
        record
        for record in records
        if record.key.root == root and record.key.order == order
    ]


def candidate_score_and_category(
    student_id: int,
    year: int,
    key: ProgramKey,
    state: DataState,
) -> tuple[int, str | None, list[str]]:
    previous = state.student_year_records.get((year - 1, student_id), [])
    following = state.student_year_records.get((year + 1, student_id), [])

    score = 0
    evidence: list[str] = []
    forced_category: str | None = None

    if key.order > 1:
        exact_previous = [
            record
            for record in previous
            if record.key == key.with_order(key.order - 1)
            and record.code in PASS_CODES
        ]
        root_previous = [
            record
            for record in previous
            if record.key.root == key.root
            and record.key.order == key.order - 1
            and record.code in PASS_CODES
        ]
        if exact_previous:
            score += 120
            evidence.append("année_précédente_parcours_exact_admis")
        elif root_previous:
            score += 95
            evidence.append("année_précédente_même_programme_admis")

    same_previous_repeat = [
        record
        for record in previous
        if record.key.root == key.root
        and record.key.order == key.order
        and record.code in REPEAT_CODES
    ]
    if same_previous_repeat:
        score += 75
        evidence.append("année_précédente_redoublement")

    if key.order < 3:
        exact_next = [
            record
            for record in following
            if record.key == key.with_order(key.order + 1)
        ]
        root_next = records_matching_root(following, key.root, key.order + 1)
        if exact_next:
            score += 120
            forced_category = "pass"
            evidence.append("année_suivante_niveau_supérieur_exact")
        elif root_next:
            score += 95
            forced_category = "pass"
            evidence.append("année_suivante_niveau_supérieur_programme")

    same_next = records_matching_root(following, key.root, key.order)
    if same_next:
        score += 85
        if forced_category is None:
            forced_category = "repeat"
        evidence.append("année_suivante_même_niveau")

    if key.order == 3 and not same_next:
        score += 10

    return score, forced_category, evidence


def candidate_pool(year: int, key: ProgramKey, state: DataState) -> list[int]:
    pool: set[int] = set()

    if key.order > 1:
        pool.update(
            state.root_order_students.get((year - 1, key.root, key.order - 1), set())
        )
    pool.update(
        state.root_order_students.get((year - 1, key.root, key.order), set())
    )
    if key.order < 3:
        pool.update(
            state.root_order_students.get((year + 1, key.root, key.order + 1), set())
        )
    pool.update(
        state.root_order_students.get((year + 1, key.root, key.order), set())
    )

    pool.difference_update(state.year_students.get(year, set()))
    return sorted(pool)


def select_real_candidates(
    plan: CellPlan,
    state: DataState,
) -> list[tuple[int, str | None, list[str]]]:
    scored: list[tuple[int, int, str | None, list[str]]] = []
    for student_id in candidate_pool(plan.year, plan.key, state):
        score, category, evidence = candidate_score_and_category(
            student_id, plan.year, plan.key, state
        )
        if score >= 70:
            scored.append((score, student_id, category, evidence))

    scored.sort(key=lambda item: (-item[0], item[1]))
    return [
        (student_id, category, evidence)
        for _, student_id, category, evidence in scored[: plan.requested]
    ]


def choose_category(
    plan: CellPlan,
    state: DataState,
    rng: random.Random,
) -> str:
    counts = Counter()

    for (root, order, category), code_counts in state.code_root.items():
        if root == plan.key.root and order == plan.key.order:
            counts[category] += sum(code_counts.values())

    allowed = ["pass", "repeat", "exit"]
    if plan.key.order == 3:
        fallback = {"pass": 0.84, "repeat": 0.08, "exit": 0.08}
    elif plan.key.order == 2:
        fallback = {"pass": 0.74, "repeat": 0.11, "exit": 0.15}
    else:
        fallback = {"pass": 0.65, "repeat": 0.15, "exit": 0.20}

    if sum(counts.values()) >= 20:
        weights = [max(1, counts[category]) for category in allowed]
    else:
        weights = [fallback[category] for category in allowed]

    return rng.choices(allowed, weights=weights, k=1)[0]


def choose_code(
    key: ProgramKey,
    category: str,
    state: DataState,
    rng: random.Random,
) -> str:
    fallback = {"pass": "ADM", "repeat": "RED", "exit": "NAR"}[category]

    counters = [
        state.code_exact.get((key, category), Counter()),
        state.code_root.get((key.root, key.order, category), Counter()),
        state.code_order.get((key.order, category), Counter()),
    ]
    counter = next((current for current in counters if sum(current.values()) > 0), None)
    if not counter:
        return fallback

    codes = sorted(counter)
    return rng.choices(codes, weights=[counter[code] for code in codes], k=1)[0]


# ============================================================
# INSERTION SÉCURISÉE
# ============================================================


def load_code_ids(cursor: Any) -> tuple[dict[str, int], dict[str, list[int]]]:
    cursor.execute(
        """
        SELECT code, codeannee_id
        FROM CodeAnnee
        ORDER BY codeannee_id
        """
    )
    grouped: dict[str, list[int]] = defaultdict(list)
    for code, code_id in cursor.fetchall():
        grouped[str(code)].append(int(code_id))
    return ({code: ids[0] for code, ids in grouped.items()}, grouped)


def get_code_id(cursor: Any, code_ids: dict[str, int], code: str) -> int:
    if code in code_ids:
        return code_ids[code]
    cursor.execute(
        """
        INSERT INTO CodeAnnee (code, signification)
        VALUES (%s, %s)
        """,
        (code, CODE_MEANINGS.get(code)),
    )
    code_ids[code] = int(cursor.lastrowid)
    return code_ids[code]


def synthetic_code(plan: CellPlan, sequence: int) -> str:
    entry_year = plan.year - (plan.key.order - 1)
    digest = hashlib.sha1(plan.key.label.encode("utf-8")).hexdigest()[:10].upper()
    return f"{GENERATED_PREFIX}{entry_year}_{digest}_{sequence:05d}"


def get_or_create_synthetic_student(
    cursor: Any,
    plan: CellPlan,
    sequence: int,
    state: DataState,
) -> tuple[int, int]:
    current_sequence = sequence
    while True:
        code_nip = synthetic_code(plan, current_sequence)
        cursor.execute(
            """
            SELECT etudiant_id
            FROM Etudiant
            WHERE code_nip = %s
            ORDER BY etudiant_id
            LIMIT 1
            """,
            (code_nip,),
        )
        row = cursor.fetchone()
        if row:
            student_id = int(row[0])
            if student_id not in state.year_students.get(plan.year, set()):
                return student_id, current_sequence + 1
            current_sequence += 1
            continue

        etat = (
            f"{GENERATED_STATE_PREFIX}|entry={plan.year - (plan.key.order - 1)}"
            f"|programme={plan.key.specialty}-{plan.key.modality}"
        )[:250]
        cursor.execute(
            """
            INSERT INTO Etudiant (code_nip, etat)
            VALUES (%s, %s)
            """,
            (code_nip, etat),
        )
        return int(cursor.lastrowid), current_sequence + 1


def insert_annual_record(
    cursor: Any,
    plan: CellPlan,
    student_id: int,
    code: str,
    code_ids: dict[str, int],
    state: DataState,
    synthetic: bool,
) -> None:
    if student_id in state.year_students.get(plan.year, set()):
        raise RuntimeError(
            f"L'étudiant {student_id} possède déjà une ligne en {plan.year}."
        )

    code_id = get_code_id(cursor, code_ids, code)
    cursor.execute(
        """
        INSERT INTO EffectuerAnnee (
            annee_scolaire,
            anneeformation_id,
            etudiant_id,
            codeannee_id
        )
        VALUES (%s, %s, %s, %s)
        """,
        (
            plan.year,
            plan.structure.anneeformation_id,
            student_id,
            code_id,
        ),
    )

    state.add_record(
        AnnualRecord(
            year=plan.year,
            student_id=student_id,
            key=plan.key,
            code=code,
            anneeformation_id=plan.structure.anneeformation_id,
            synthetic=synthetic,
        )
    )


def execute_plan(
    cursor: Any,
    plans: Sequence[CellPlan],
    state: DataState,
    code_ids: dict[str, int],
) -> int:
    total_inserted = 0

    for plan in plans:
        real_candidates = select_real_candidates(plan, state)
        plan.real_candidates = len(real_candidates)
        rng = deterministic_rng(plan.year, plan.key.label)

        for student_id, forced_category, evidence in real_candidates:
            if plan.inserted_total >= plan.requested:
                break
            category = forced_category or choose_category(plan, state, rng)
            code = choose_code(plan.key, category, state, rng)
            insert_annual_record(
                cursor,
                plan,
                student_id,
                code,
                code_ids,
                state,
                synthetic=False,
            )
            plan.inserted_real += 1
            plan.inserted_total += 1
            plan.decisions[code] = plan.decisions.get(code, 0) + 1
            if evidence and len(plan.notes) < 4:
                plan.notes.append("; ".join(evidence))
            total_inserted += 1

        remaining = plan.requested - plan.inserted_total
        if remaining <= 0:
            plan.status = "completed"
            continue

        if not ALLOW_SYNTHETIC:
            plan.status = "partial_no_synthetic"
            plan.notes.append("Étudiants synthétiques désactivés.")
            continue

        max_synthetic = int(math.floor(plan.requested * MAX_SYNTHETIC_RATIO))
        allowed_synthetic = min(remaining, max_synthetic)
        if allowed_synthetic < remaining:
            plan.notes.append(
                f"Plafond synthétique : {allowed_synthetic}/{remaining} autorisés."
            )

        sequence = 1
        for _ in range(allowed_synthetic):
            if total_inserted >= MAX_INSERTIONS:
                raise RuntimeError(
                    f"Garde-fou MAX_INSERTIONS={MAX_INSERTIONS} atteint."
                )
            student_id, sequence = get_or_create_synthetic_student(
                cursor, plan, sequence, state
            )
            category = choose_category(plan, state, rng)
            code = choose_code(plan.key, category, state, rng)
            insert_annual_record(
                cursor,
                plan,
                student_id,
                code,
                code_ids,
                state,
                synthetic=True,
            )
            plan.inserted_synthetic += 1
            plan.inserted_total += 1
            plan.decisions[code] = plan.decisions.get(code, 0) + 1
            total_inserted += 1

        plan.status = (
            "completed" if plan.inserted_total == plan.requested else "partial_safety_limit"
        )

    return total_inserted


# ============================================================
# VALIDATION APRÈS INSERTION
# ============================================================


def validate_results(
    cursor: Any,
    plans: Sequence[CellPlan],
    structures_by_id: Mapping[int, Structure],
) -> dict[str, Any]:
    inserted_expectations = {
        (plan.year, plan.key): plan.current + plan.inserted_total
        for plan in plans
    }

    cursor.execute(
        """
        SELECT
            ea.annee_scolaire,
            ea.anneeformation_id,
            ea.etudiant_id
        FROM EffectuerAnnee ea
        """
    )
    actual_cells: dict[tuple[int, ProgramKey], set[int]] = defaultdict(set)
    student_orders: dict[tuple[int, int], set[int]] = defaultdict(set)

    for year, anneeformation_id, student_id in cursor.fetchall():
        structure = structures_by_id.get(int(anneeformation_id))
        if structure is None:
            continue
        actual_cells[(int(year), structure.key)].add(int(student_id))
        student_orders[(int(year), int(student_id))].add(structure.key.order)

    mismatches = []
    for (year, key), expected in inserted_expectations.items():
        actual = len(actual_cells.get((year, key), set()))
        if actual != expected:
            mismatches.append({
                "annee": year,
                "programme": key.label,
                "attendu": expected,
                "obtenu": actual,
            })

    generated_conflicts = []
    for (year, student_id), orders in student_orders.items():
        if len(orders) <= 1:
            continue
        cursor.execute(
            "SELECT code_nip, etat FROM Etudiant WHERE etudiant_id = %s",
            (student_id,),
        )
        row = cursor.fetchone()
        if row and (
            str(row[0] or "").startswith(GENERATED_PREFIX)
            or str(row[1] or "").startswith(GENERATED_STATE_PREFIX)
        ):
            generated_conflicts.append({
                "annee": year,
                "etudiant_id": student_id,
                "niveaux": sorted(orders),
            })

    return {
        "valide": not mismatches and not generated_conflicts,
        "ecarts_effectifs": mismatches[:REPORT_LIMIT],
        "conflits_synthetiques": generated_conflicts[:REPORT_LIMIT],
    }


# ============================================================
# PROGRAMME PRINCIPAL
# ============================================================


def main() -> None:
    result: dict[str, Any] = {
        "success": False,
        "message": "",
        "config": {
            "dry_run": DRY_RUN,
            "fill_mode": FILL_MODE,
            "min_confidence": MIN_CONFIDENCE,
            "allow_synthetic": ALLOW_SYNTHETIC,
            "max_synthetic_ratio": MAX_SYNTHETIC_RATIO,
            "max_insertions": MAX_INSERTIONS,
            "rebuild_generated": REBUILD_GENERATED,
            "use_reference_caps": USE_REFERENCE_CAPS,
            "seed": SEED,
        },
        "annees": [],
        "nettoyage": {},
        "resume": {},
        "details": [],
        "ignores": [],
        "validation": {},
        "warnings": [],
        "errors": [],
    }

    connection = None
    cursor = None
    lock_acquired = False

    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        connection.autocommit = False
        cursor = connection.cursor()

        validate_schema(cursor)
        acquire_lock(cursor)
        lock_acquired = True

        years = determine_years(cursor)
        result["annees"] = years
        result["nettoyage"] = rebuild_generated_rows(cursor, years)

        structures_by_id, structures_by_key = load_structures(cursor)
        state = load_state(cursor, structures_by_id)
        plans, skipped = build_plan(years, structures_by_key, state)

        planned_insertions = sum(plan.requested for plan in plans)
        if planned_insertions > MAX_INSERTIONS:
            raise RuntimeError(
                f"Le plan demande {planned_insertions} insertions, au-dessus de "
                f"MAX_INSERTIONS={MAX_INSERTIONS}."
            )

        code_ids, duplicate_code_ids = load_code_ids(cursor)
        duplicates = {
            code: ids
            for code, ids in duplicate_code_ids.items()
            if len(ids) > 1
        }
        if duplicates:
            result["warnings"].append({
                "codes_annee_dupliques": duplicates,
                "comportement": "le plus petit identifiant est utilisé",
            })

        total_inserted = execute_plan(cursor, plans, state, code_ids)
        validation = validate_results(cursor, plans, structures_by_id)
        result["validation"] = validation

        if not validation["valide"]:
            raise RuntimeError(
                "La validation finale a détecté des écarts ou des conflits."
            )

        completed = sum(1 for plan in plans if plan.status == "completed")
        partial = sum(1 for plan in plans if plan.status.startswith("partial"))
        real_count = sum(plan.inserted_real for plan in plans)
        synthetic_count = sum(plan.inserted_synthetic for plan in plans)

        result["resume"] = {
            "cellules_planifiees": len(plans),
            "cellules_completees": completed,
            "cellules_partielles": partial,
            "cellules_ignorees": len(skipped),
            "insertions": total_inserted,
            "etudiants_reels_reutilises": real_count,
            "etudiants_synthetiques": synthetic_count,
        }
        result["details"] = [plan.report() for plan in plans[:REPORT_LIMIT]]
        result["ignores"] = skipped[:REPORT_LIMIT]

        if DRY_RUN:
            connection.rollback()
            result["success"] = True
            result["message"] = (
                f"Simulation réussie : {total_inserted} lignes seraient ajoutées "
                f"dans {len(plans)} cellule(s)."
            )
        else:
            connection.commit()
            result["success"] = True
            result["message"] = (
                f"Complétion réussie : {total_inserted} lignes ajoutées "
                f"dans {len(plans)} cellule(s)."
            )

    except Error as exc:
        if connection is not None:
            connection.rollback()
        result["message"] = "Erreur MySQL pendant la complétion."
        result["errors"].append(str(exc))
    except Exception as exc:
        if connection is not None:
            connection.rollback()
        result["message"] = "Erreur pendant la complétion."
        result["errors"].append(f"{type(exc).__name__}: {exc}")
    finally:
        if cursor is not None and lock_acquired:
            release_lock(cursor)
        if cursor is not None:
            cursor.close()
        if connection is not None and connection.is_connected():
            connection.close()

    print(json.dumps(result, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()