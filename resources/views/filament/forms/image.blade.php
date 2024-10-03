<?php
if($getRecord() != null) {
    $record = $getRecord();
    $furl = app()->isLocal() ? getenv('FRONTEND_DEVURL') : getenv('FRONTEND_URL');
    $url = $furl . "?" . http_build_query([
        'id' => $record->id,
    ]);
}

?>

<div class="fi-fo-field-wrp">
    <div class="grid gap-y-2">
        <div class="flex items-center gap-x-3 justify-between ">
            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="data.name">
                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    @if($getRecord()->qrcode != '')
                        Download QR A4 document
                    @else
                        QR code image
                    @endif

                </span>
            </label>
        </div>
        <div class="grid auto-cols-fr gap-y-2">
            <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 fi-disabled bg-gray-50 dark:bg-transparent ring-gray-950/10 dark:ring-white/10 fi-fo-text-input overflow-hidden">
                <div class="min-w-0 flex-1">
                    @if($getRecord()->qrcode != '')
                        <a href="{{ route('document', $getRecord()->code) }}" style="width: 100%; height: auto;" target="_blank">
                            <img src="{{ $getRecord()->qrcode }}" alt="Image" style="width: 100%; height: auto;" />
                        </a>
                    @else
                        <img src="https://placehold.co/1000x1000" alt="Image" style="width: 100%; height: auto;" />
                    @endif
                </div>

            </div>
            @if($getRecord()->qrcode != '')
                <div class="fi-fo-field-wrp-helper-text text-sm text-gray-500">
                    <a href="{{ $url }}" target="_blank">
                        {{ $url }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>


