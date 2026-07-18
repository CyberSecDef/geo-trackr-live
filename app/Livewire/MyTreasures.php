<?php

namespace App\Livewire;

use App\Models\Treasure;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class MyTreasures extends Component
{
    /** @return Collection<int, Treasure> */
    public function getTreasuresProperty(): Collection
    {
        return Treasure::where('user_id', auth()->id())
            ->withCount('unlocks')
            ->latest()
            ->get();
    }

    public function togglePause(int $id): void
    {
        $treasure = $this->ownedOrFail($id);
        $treasure->update([
            'status' => $treasure->isActive() ? 'paused' : 'active',
        ]);
    }

    public function delete(int $id): void
    {
        // Cascade removes the image BLOB and unlock rows (FK onDelete cascade).
        $this->ownedOrFail($id)->delete();
    }

    private function ownedOrFail(int $id): Treasure
    {
        return Treasure::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.my-treasures', ['treasures' => $this->treasures]);
    }
}
