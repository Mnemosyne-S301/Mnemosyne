<?php
class CodeUE {
    private string $code;
    private ?string $signification;
    public function __construct(array $d) {
        $this->code = (string)$d['code'];
        $this->signification = $d['signification'] ?? null;
    }
    public function toDict(): array { return ['code'=>$this->code,'signification'=>$this->signification]; }
}
