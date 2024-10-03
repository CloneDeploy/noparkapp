<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

use function Livewire\Volt\{computed, state};



state([
    'role' => fn () => auth()->user()->role,
]);

$admin = computed(fn () => auth()->user()->role === 'admin');

$updateRole = function () {
    $user = Auth::user();

    $validated = $this->validate([
        'role' => ['required', 'string', 'max:255'],
    ]);

    $user->fill($validated);
    $user->save();

    $this->dispatch('role-updated', name: $user->role);
};

?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('User role') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's role.") }}
        </p>
    </header>

    <form wire:submit="updateRole" class="mt-6 space-y-6">
        <div>
            <x-input-label for="role" :value="__('Role')" />
            <x-select-input wire:model="role" id="role" name="role" type="text" class="mt-1 block w-full" required autofocus autocomplete="role" disabled="{{ !$this->admin }}">
                <option value="user">Standard user</option>
                <option value="group">Group user</option>
                <option value="admin">User admin</option>
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('role')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="role-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
