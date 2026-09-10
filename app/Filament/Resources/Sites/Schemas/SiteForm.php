<?php

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
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
                    ->maxSize(102400)
                    ->maxParallelUploads(3)
                    ->columnSpanFull(),
            ]);
    }
}
