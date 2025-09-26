<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'source_path',
        'source_relation_number',
        'source_relation_name',
        'destination_path',
    ];

    public function destFormatted()
    {
        if($this->attributes['destination_path'] === null) {
            return "niet gemapped";
        }

        if($this->attributes['destination_path'] == "-") {
            return "negeren";
        }

        return $this->attributes['destination_path'];
    }
}
