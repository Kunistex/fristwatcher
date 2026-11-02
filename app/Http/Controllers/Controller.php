<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    /** Fremde Datensätze sind für den Nutzer unsichtbar (404 statt 403). */
    protected function owns(Model $model): void
    {
        abort_unless((int) $model->getAttribute('user_id') === (int) auth()->id(), 404);
    }
}
