#!/usr/bin/env python3
from __future__ import annotations

import json
import math
import os
import random
import unicodedata
from collections import Counter, defaultdict
from typing import Any

import mysql.connector
from mysql.connector import Error


DB_CONFIG = {
    "host": os.getenv("DB_HOST", "mnemosyne-mnemosyne-mysql-1"),
    "port": int(os.getenv("DB_PORT", "3306")),
    "user": os.getenv("DB_USER", "phpserv"),
    "password": os.getenv("DB_PASSWORD", "phpserv"),
    "database": os.getenv("DB_NAME", "scolarite"),
}

ANNEE_CIBLE = int(os.getenv("TARGET_YEAR", "2022"))
DRY_RUN = os.getenv("DRY_RUN", "0").lower() in {"1", "true", "yes", "oui"}
random.seed(301)

# Capacités 2025 et taux publics de passage BUT1 -> BUT2.
REFERENCES_IUT = {
    "INFO": {"but1": 104, "passage": 0.643},
    "RT": {"but1": 74, "passage": 0.644},
    "SD": {"but1": 56, "passage": 0.726},
    "GEII": {"but1": 100, "passage": 0.618},
    "GEA": {"but1": 140, "passage": 0.740},
    "CJ": {"but1": 150, "passage": 0.626},
}

CODES_PASSAGE = {"ADM", "ADJ", "PASD", "PAS1NCI"}
CODES_REDOUBLEMENT = {"RED"}


def normaliser(texte: str | None) -> str:
    return (
        unicodedata.normalize("NFKD", texte or "")
        .encode("ascii", "ignore")
        .decode("ascii")
        .upper()
        .strip()
    )


def specialite(accronyme: str | None) -> str | None:
    texte = normaliser(accronyme)
    if not texte.startswith("BUT"):
        return None
    if "GEII" in texte:
        return "GEII"
    if "GEA" in texte:
        return "GEA"
    if "INFO" in texte or "INFORMATIQUE" in texte:
        return "INFO"
    if (
        "R&T" in (accronyme or "").upper()
        or "RESEAUX" in texte
        or "TELECOMMUNICATION" in texte
    ):
        return "RT"
    if texte.startswith("BUT SD") or "SCIENCE DES DONNEES" in texte:
        return "SD"
    if texte.startswith("BUT CJ") or "CARRIERES JURIDIQUES" in texte:
        return "CJ"
    return None


def objectif(nom_specialite: str, ordre: int) -> int:
    ref = REFERENCES_IUT.get(nom_specialite)
    if not ref:
        return 0
    if ordre == 1:
        return int(ref["but1"])
    if ordre == 2:
        return round(int(ref["but1"]) * float(ref["passage"]))
    return 0


def charger_correspondances(cursor: Any) -> dict[tuple[int, int], int]:
    cursor.execute(
        """
        SELECT af.anneeformation_id, af.ordre, af.parcours_id,
               COUNT(ea.etudiant_id) AS utilisations
        FROM AnneeFormation af
        LEFT JOIN EffectuerAnnee ea
          ON ea.anneeformation_id = af.anneeformation_id
        WHERE af.ordre IN (1, 2, 3)
        GROUP BY af.anneeformation_id, af.ordre, af.parcours_id
        ORDER BY utilisations DESC, af.anneeformation_id
        """
    )
    resultat: dict[tuple[int, int], int] = {}
    for anneeformation_id, ordre, parcours_id, _ in cursor.fetchall():
        if ordre is None or parcours_id is None:
            continue
        resultat.setdefault(
            (int(parcours_id), int(ordre)),
            int(anneeformation_id),
        )
    return resultat


def charger_deja_presents(cursor: Any) -> set[int]:
    cursor.execute(
        """
        SELECT DISTINCT etudiant_id
        FROM EffectuerAnnee
        WHERE annee_scolaire = %s
        """,
        (ANNEE_CIBLE,),
    )
    return {int(row[0]) for row in cursor.fetchall()}


def charger_effectifs_existants(
    cursor: Any,
) -> dict[tuple[str, int], int]:
    cursor.execute(
        """
        SELECT DISTINCT ea.etudiant_id, af.ordre, f.accronyme
        FROM EffectuerAnnee ea
        JOIN AnneeFormation af
          ON af.anneeformation_id = ea.anneeformation_id
        JOIN Parcours p
          ON p.parcours_id = af.parcours_id
        JOIN Formation f
          ON f.formation_id = p.formation_id
        WHERE ea.annee_scolaire = %s
        """,
        (ANNEE_CIBLE,),
    )
    groupes: dict[tuple[str, int], set[int]] = defaultdict(set)
    for etudiant_id, ordre, accronyme in cursor.fetchall():
        nom_specialite = specialite(accronyme)
        if nom_specialite and ordre:
            groupes[(nom_specialite, int(ordre))].add(int(etudiant_id))
    return {cle: len(ids) for cle, ids in groupes.items()}


def charger_repartition_codes(
    cursor: Any,
) -> dict[tuple[str, int], Counter[str]]:
    cursor.execute(
        """
        SELECT f.accronyme, af.ordre, ca.code,
               COUNT(DISTINCT ea.etudiant_id)
        FROM EffectuerAnnee ea
        JOIN AnneeFormation af
          ON af.anneeformation_id = ea.anneeformation_id
        JOIN Parcours p
          ON p.parcours_id = af.parcours_id
        JOIN Formation f
          ON f.formation_id = p.formation_id
        JOIN CodeAnnee ca
          ON ca.codeannee_id = ea.codeannee_id
        WHERE ea.annee_scolaire <> %s
        GROUP BY f.accronyme, af.ordre, ca.code
        """,
        (ANNEE_CIBLE,),
    )
    resultat: dict[tuple[str, int], Counter[str]] = defaultdict(Counter)
    for accronyme, ordre, code, nombre in cursor.fetchall():
        nom_specialite = specialite(accronyme)
        if nom_specialite and ordre and code:
            resultat[(nom_specialite, int(ordre))][str(code)] += int(nombre)
    return resultat


def choisir_code(
    repartitions: dict[tuple[str, int], Counter[str]],
    nom_specialite: str,
    ordre: int,
) -> str:
    compteur = repartitions.get((nom_specialite, ordre), Counter())
    codes = [
        code
        for code in sorted(CODES_PASSAGE)
        if compteur.get(code, 0) > 0
    ]
    if not codes:
        return "ADM"
    return random.choices(
        codes,
        weights=[compteur[code] for code in codes],
        k=1,
    )[0]


def charger_ids_codes(cursor: Any) -> dict[str, int]:
    cursor.execute(
        """
        SELECT code, MIN(codeannee_id)
        FROM CodeAnnee
        GROUP BY code
        """
    )
    return {
        str(code): int(code_id)
        for code, code_id in cursor.fetchall()
        if code is not None and code_id is not None
    }


def obtenir_id_code(
    cursor: Any,
    ids_codes: dict[str, int],
    code: str,
) -> int:
    if code in ids_codes:
        return ids_codes[code]

    significations = {
        "ADM": "Admis",
        "ADJ": "Admis par décision du jury",
        "PASD": "Passage de droit",
        "PAS1NCI": "Passage sous condition",
        "RED": "Redoublement",
    }
    cursor.execute(
        """
        INSERT INTO CodeAnnee (code, signification)
        VALUES (%s, %s)
        """,
        (code, significations.get(code)),
    )
    ids_codes[code] = int(cursor.lastrowid)
    return ids_codes[code]


def ajouter_candidat(
    candidats: dict[int, dict[str, Any]],
    correspondances: dict[tuple[int, int], int],
    etudiant_id: int,
    parcours_id: int,
    code_parcours: str | None,
    accronyme: str | None,
    ordre_cible: int,
    type_code: str,
    origine: str,
) -> bool:
    if etudiant_id in candidats:
        return True

    nom_specialite = specialite(accronyme)
    if nom_specialite is None:
        return True

    anneeformation_id = correspondances.get((parcours_id, ordre_cible))
    if anneeformation_id is None:
        return False

    candidats[etudiant_id] = {
        "anneeformation_id": anneeformation_id,
        "parcours_id": parcours_id,
        "code_parcours": code_parcours or "",
        "specialite": nom_specialite,
        "accronyme": accronyme or "",
        "ordre": ordre_cible,
        "type_code": type_code,
        "origine": origine,
    }
    return True


def construire_candidats(
    cursor: Any,
    correspondances: dict[tuple[int, int], int],
) -> tuple[dict[int, dict[str, Any]], int]:
    candidats: dict[int, dict[str, Any]] = {}
    sans_structure = 0

    # BUT2 2023 -> BUT1 2022 ; BUT3 2023 -> BUT2 2022.
    cursor.execute(
        """
        SELECT DISTINCT ea.etudiant_id, af.ordre, af.parcours_id,
                        p.code, f.accronyme
        FROM EffectuerAnnee ea
        JOIN AnneeFormation af
          ON af.anneeformation_id = ea.anneeformation_id
        JOIN Parcours p
          ON p.parcours_id = af.parcours_id
        JOIN Formation f
          ON f.formation_id = p.formation_id
        WHERE ea.annee_scolaire = %s
          AND af.ordre IN (2, 3)
        ORDER BY ea.etudiant_id, af.ordre DESC, af.parcours_id
        """,
        (ANNEE_CIBLE + 1,),
    )

    for etudiant_id, ordre, parcours_id, code_parcours, accronyme in cursor.fetchall():
        if not ajouter_candidat(
            candidats,
            correspondances,
            int(etudiant_id),
            int(parcours_id),
            code_parcours,
            accronyme,
            int(ordre) - 1,
            "PASSAGE",
            f"reconstruction depuis {ANNEE_CIBLE + 1}",
        ):
            sans_structure += 1

    # Projection secondaire depuis 2021.
    cursor.execute(
        """
        SELECT ea.etudiant_id, af.ordre, af.parcours_id,
               p.code, f.accronyme,
               GROUP_CONCAT(DISTINCT ca.code ORDER BY ca.code)
        FROM EffectuerAnnee ea
        JOIN AnneeFormation af
          ON af.anneeformation_id = ea.anneeformation_id
        JOIN Parcours p
          ON p.parcours_id = af.parcours_id
        JOIN Formation f
          ON f.formation_id = p.formation_id
        JOIN CodeAnnee ca
          ON ca.codeannee_id = ea.codeannee_id
        WHERE ea.annee_scolaire = %s
        GROUP BY ea.etudiant_id, af.ordre, af.parcours_id,
                 p.code, f.accronyme
        ORDER BY ea.etudiant_id, af.ordre DESC, af.parcours_id
        """,
        (ANNEE_CIBLE - 1,),
    )

    for (
        etudiant_id,
        ordre,
        parcours_id,
        code_parcours,
        accronyme,
        codes_str,
    ) in cursor.fetchall():
        etudiant_id = int(etudiant_id)
        if etudiant_id in candidats:
            continue

        codes = {
            code.strip()
            for code in str(codes_str or "").split(",")
            if code.strip()
        }

        if codes.intersection(CODES_PASSAGE) and int(ordre) < 3:
            ordre_cible = int(ordre) + 1
            type_code = "PASSAGE"
        elif codes.intersection(CODES_REDOUBLEMENT):
            ordre_cible = int(ordre)
            type_code = "RED"
        else:
            continue

        if not ajouter_candidat(
            candidats,
            correspondances,
            etudiant_id,
            int(parcours_id),
            code_parcours,
            accronyme,
            ordre_cible,
            type_code,
            f"projection depuis {ANNEE_CIBLE - 1}",
        ):
            sans_structure += 1

    return candidats, sans_structure


def repartir_quota(
    disponibilites: dict[int, int],
    quota_total: int,
) -> dict[int, int]:
    if quota_total <= 0:
        return {parcours_id: 0 for parcours_id in disponibilites}

    total = sum(disponibilites.values())
    if total <= quota_total:
        return dict(disponibilites)

    quotas: dict[int, int] = {}
    restes: list[tuple[float, int]] = []

    for parcours_id, disponible in disponibilites.items():
        exact = quota_total * disponible / total
        quotas[parcours_id] = min(disponible, math.floor(exact))
        restes.append((exact - quotas[parcours_id], parcours_id))

    restant = quota_total - sum(quotas.values())
    for _, parcours_id in sorted(
        restes,
        key=lambda item: (-item[0], item[1]),
    ):
        if restant == 0:
            break
        if quotas[parcours_id] < disponibilites[parcours_id]:
            quotas[parcours_id] += 1
            restant -= 1

    return quotas


def calibrer_candidats(
    candidats: dict[int, dict[str, Any]],
    deja_presents: set[int],
    effectifs_existants: dict[tuple[str, int], int],
) -> tuple[dict[int, dict[str, Any]], dict[str, Any]]:
    groupes: dict[
        tuple[str, int],
        dict[int, list[tuple[int, dict[str, Any]]]],
    ] = defaultdict(lambda: defaultdict(list))

    for etudiant_id, candidat in candidats.items():
        if etudiant_id in deja_presents:
            continue

        nom_specialite = str(candidat["specialite"])
        ordre = int(candidat["ordre"])
        if nom_specialite not in REFERENCES_IUT or ordre not in (1, 2):
            continue

        groupes[(nom_specialite, ordre)][
            int(candidat["parcours_id"])
        ].append((etudiant_id, candidat))

    selection: dict[int, dict[str, Any]] = {}
    statistiques: dict[str, Any] = {}

    for nom_specialite in sorted(REFERENCES_IUT):
        for ordre in (1, 2):
            cle = (nom_specialite, ordre)
            par_parcours = groupes.get(cle, {})
            cible = objectif(nom_specialite, ordre)
            existants = effectifs_existants.get(cle, 0)
            places = max(0, cible - existants)

            disponibilites = {
                parcours_id: len(elements)
                for parcours_id, elements in par_parcours.items()
            }
            quotas = repartir_quota(disponibilites, places)
            retenus = 0

            for parcours_id, elements in par_parcours.items():
                elements.sort(
                    key=lambda element: (
                        0
                        if element[1]["origine"].startswith("reconstruction")
                        else 1,
                        element[0],
                    )
                )
                for etudiant_id, candidat in elements[
                    : quotas.get(parcours_id, 0)
                ]:
                    selection[etudiant_id] = candidat
                    retenus += 1

            statistiques[f"{nom_specialite}_BUT{ordre}"] = {
                "objectif_total": cible,
                "deja_existants": existants,
                "places_restantes": places,
                "candidats_disponibles": sum(disponibilites.values()),
                "retenus": retenus,
            }

    return selection, statistiques


def etudiant_absent_de_annee(cursor: Any, etudiant_id: int) -> bool:
    cursor.execute(
        """
        SELECT 1
        FROM EffectuerAnnee
        WHERE annee_scolaire = %s
          AND etudiant_id = %s
        LIMIT 1
        """,
        (ANNEE_CIBLE, etudiant_id),
    )
    return cursor.fetchone() is None


def main() -> None:
    resultat: dict[str, Any] = {
        "success": False,
        "message": "",
        "annee_cible": ANNEE_CIBLE,
        "dry_run": DRY_RUN,
        "candidats_avant_calibrage": 0,
        "candidats_apres_calibrage": 0,
        "insertions": 0,
        "deja_presents": 0,
        "sans_structure": 0,
        "par_origine": {},
        "par_specialite": {},
        "par_ordre": {},
        "calibrage_iut": {},
        "errors": [],
    }

    connexion = None
    cursor = None

    try:
        connexion = mysql.connector.connect(**DB_CONFIG)
        connexion.autocommit = False
        cursor = connexion.cursor()

        correspondances = charger_correspondances(cursor)
        deja_presents = charger_deja_presents(cursor)
        effectifs_existants = charger_effectifs_existants(cursor)
        repartitions_codes = charger_repartition_codes(cursor)
        ids_codes = charger_ids_codes(cursor)

        candidats, sans_structure = construire_candidats(
            cursor,
            correspondances,
        )

        resultat["sans_structure"] = sans_structure
        resultat["candidats_avant_calibrage"] = len(candidats)
        resultat["deja_presents"] = sum(
            etudiant_id in deja_presents
            for etudiant_id in candidats
        )

        candidats, statistiques = calibrer_candidats(
            candidats,
            deja_presents,
            effectifs_existants,
        )

        resultat["candidats_apres_calibrage"] = len(candidats)
        resultat["calibrage_iut"] = statistiques

        par_origine: Counter[str] = Counter()
        par_specialite: Counter[str] = Counter()
        par_ordre: Counter[str] = Counter()

        for etudiant_id, candidat in sorted(candidats.items()):
            if not etudiant_absent_de_annee(cursor, etudiant_id):
                resultat["deja_presents"] += 1
                continue

            nom_specialite = str(candidat["specialite"])
            ordre = int(candidat["ordre"])
            code = (
                "RED"
                if candidat["type_code"] == "RED"
                else choisir_code(
                    repartitions_codes,
                    nom_specialite,
                    ordre,
                )
            )

            codeannee_id = obtenir_id_code(
                cursor,
                ids_codes,
                code,
            )

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
                    ANNEE_CIBLE,
                    int(candidat["anneeformation_id"]),
                    etudiant_id,
                    codeannee_id,
                ),
            )

            resultat["insertions"] += 1
            par_origine[str(candidat["origine"])] += 1
            par_specialite[nom_specialite] += 1
            par_ordre[f"BUT{ordre}"] += 1

        resultat["par_origine"] = dict(par_origine)
        resultat["par_specialite"] = dict(par_specialite)
        resultat["par_ordre"] = dict(par_ordre)

        if DRY_RUN:
            connexion.rollback()
            resultat["success"] = True
            resultat["message"] = (
                f"Simulation terminée : {resultat['insertions']} lignes "
                f"seraient ajoutées pour l'année {ANNEE_CIBLE}."
            )
        else:
            connexion.commit()
            resultat["success"] = True
            resultat["message"] = (
                f"Synchronisation terminée : {resultat['insertions']} "
                f"lignes cohérentes ajoutées pour l'année {ANNEE_CIBLE}."
            )

    except Error as error:
        if connexion is not None:
            connexion.rollback()
        resultat["message"] = "Erreur MySQL pendant la synchronisation."
        resultat["errors"].append(str(error))

    except Exception as error:
        if connexion is not None:
            connexion.rollback()
        resultat["message"] = "Erreur inattendue pendant la synchronisation."
        resultat["errors"].append(f"{type(error).__name__}: {error}")

    finally:
        if cursor is not None:
            cursor.close()
        if connexion is not None and connexion.is_connected():
            connexion.close()

    print(json.dumps(resultat, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()