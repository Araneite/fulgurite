<?php

interface OutboundUrlValidator {
    /**
     * @return array<string,mixed>
     */
    public function validate(string $url): array;
}
