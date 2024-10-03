<?php

namespace App\Filament\Resources\LocationResource\Pages;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Filament\Actions;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\LocationResource;
use Barryvdh\DomPDF\Facade\Pdf as PDF;


class ViewLocation extends ViewRecord
{
    protected static string $resource = LocationResource::class;

    protected $listeners = ['dataFetched'];

    public $temp = null;

    public function dataError($data): void
    {
        $this->temp = null;
        Log::info($data);
    }

    public function generateQr($text): void
    {


    }

    public function dataFetched($data): void
    {
        $icon = urlencode('https://i.ibb.co/q0DRMyj/pi.jpg');
        $this->temp->qrcode = "https://quickchart.io/qr?text=$data&centerImageUrl=$icon&size=1000";
        $this->temp->save();
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('generateqr')
                ->label('Generate QR')
                ->action(function (Model $record): void {
                    $ext = 'png';
                    $icon = DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'parking_bw.png';
                    $loc = 'images/' . $record->code . '.' . $ext;
                    $path = public_path($loc);
                    $furl = app()->isLocal() ? env('FRONTEND_DEVURL') : env('FRONTEND_URL');
                    $image = QrCode::format($ext)
                        ->size(1000)
                        ->color(0, 0, 0)
                        ->margin(2)
                        ->merge($icon, .2)
                        ->generate($furl . "?" . http_build_query(['id' => $record->id]), $path);
                    $image = url($loc);
                    $record->qrcode = $image;
                    $record->save();

                    $pdfloc = 'documents/' . $record->code . '.pdf';
                    $pdfpath = public_path($pdfloc);
                    Pdf::loadView('qrscan', ['image' => $path])
                        ->setPaper('a4')
                        ->save($pdfpath);
                }),
        ];
    }
}
