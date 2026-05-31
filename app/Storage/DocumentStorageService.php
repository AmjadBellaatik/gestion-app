<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\Storage;

class DocumentStorageService
{
    public static function uploadLogo(
        UploadedFile $file
    ): string {

        return $file->store(

            'logos',

            'public'

        );
    }

    public static function uploadStamp(
        UploadedFile $file
    ): string {

        return $file->store(

            'stamps',

            'public'

        );
    }

    public static function uploadSignature(
        UploadedFile $file
    ): string {

        return $file->store(

            'signatures',

            'public'

        );
    }

    public static function uploadTemplate(
        UploadedFile $file
    ): string {

        return $file->store(

            'templates',

            'public'

        );
    }

    public static function savePdf(
        string $content,
        string $filename
    ): string {

        $path =

            'documents/' .

            $filename;

        Storage::disk('public')->put(

            $path,

            $content

        );

        return $path;
    }

    public static function saveConformityPdf(
        string $content,
        string $filename
    ): string {

        $path =

            'conformity/' .

            $filename;

        Storage::disk('public')->put(

            $path,

            $content

        );

        return $path;
    }

    public static function saveWarrantyPdf(
        string $content,
        string $filename
    ): string {

        $path =

            'warranty/' .

            $filename;

        Storage::disk('public')->put(

            $path,

            $content

        );

        return $path;
    }

    public static function delete(
        ?string $path
    ): void {

        if (! $path) {

            return;
        }

        if (

            Storage::disk('public')
                ->exists($path)

        ) {

            Storage::disk('public')
                ->delete($path);

        }
    }
}