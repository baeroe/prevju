<?php

namespace App\Filament\Resources\Sites\Schemas;

use App\Models\Site;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('slug')
                    ->default(fn () => Str::lower(Str::random(10))),
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('password')
                    ->label('Passwort')
                    ->password()
                    ->revealable()
                    ->formatStateUsing(fn () => null)
                    ->helperText('Leer lassen = kein Passwort. Beim Bearbeiten bleibt das bestehende Passwort erhalten.')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),
                Checkbox::make('clear_password')
                    ->label('Passwort entfernen')
                    ->visibleOn('edit')
                    ->dehydrated(false),
                FileUpload::make('files')
                    ->label('Dateien (HTML, CSS, JS, Bilder oder eine ZIP mit Ordnerstruktur)')
                    ->disk('sites')
                    ->directory(fn (Get $get) => $get('slug'))
                    ->multiple()
                    ->preserveFilenames()
                    ->previewable(false)
                    ->maxSize(102400)
                    ->maxParallelUploads(3)
                    ->columnSpanFull(),
                Placeholder::make('file_list')
                    ->label('Dateien auf dem Server')
                    ->visibleOn('edit')
                    ->columnSpanFull()
                    ->content(fn (?Site $record) => new HtmlString(
                        $record?->fileList()
                            ->map(fn ($f) => '<li><a class="underline" target="_blank" href="'.e($record->url().$f).'">'.e($f).'</a></li>')
                            ->whenEmpty(fn () => collect(['<li>Keine Dateien</li>']))
                            ->prepend('<ul class="list-disc pl-5 text-sm">')
                            ->push('</ul>')
                            ->implode('')
                    )),
            ]);
    }
}
