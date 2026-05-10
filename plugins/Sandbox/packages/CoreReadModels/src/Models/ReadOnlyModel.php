<?php

namespace Plugins\CoreReadModels\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

abstract class ReadOnlyModel extends Model
{
	protected function blockWrite(): never {
        throw new LogicException(static::class . " is read-only inside plugin sandbox.");
    }
    
    public function save(array $options = []): bool {
        $this->blockWrite();
    }
    
    public function update(array $attributes = [], array $options = []): bool {
        $this->blockWrite();
    }
    
    public function delete(): ?bool {
        $this->blockWrite();
    }
    
    public function forceDelete(): ?bool {
        $this->blockWrite();
    }
    
    public function restore(): bool {
        $this->blockWrite();
    }
    
    public function performInsert($query): bool {
        $this->blockWrite();
    }
    
    public function performUpdate($query): bool {
        $this->blockWrite();
    }
}
