<?php

class SshHostKeyException extends RuntimeException {
    public function __construct(
        string $message,
        private array $details = []
    ) {
        parent::__construct($message);
    }

    public function details(): array {
        return $this->details;
    }

    public function toArray(): array {
        return $this->details;
    }
}

class UnknownHostKeyException extends SshHostKeyException {}

class ChangedHostKeyException extends SshHostKeyException {}
