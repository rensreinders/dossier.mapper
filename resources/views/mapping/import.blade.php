@extends('layouts.app')

@section('title', 'Mapping')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-3">CSV uploaden</h1>

        <blockquote class="text-muted">
            <p>Upload een CSV-bestand om bron- en doelpaden te mappen. De eerste rij moet de kolomkoppen bevatten.</p>
            <p>Voorbeeld CSV-inhoud:</p>
            <pre>
                bron_pad;relatienummer;relatienaam
                /Samenstel/12345/IB;12345;Bedrijf BV
                /Samenstel/67890/IB;67890;Another Company NV
            </pre>
        </blockquote>

        <form method="post" action="{{ route('mapping.import.process') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="csv_file" class="form-label">Kies CSV-bestand</label>
                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
            <button type="button" class="btn btn-primary"
                    onclick="this.disabled=true; this.innerHTML='Bezig met uploaden...'; this.form.submit();"
            >Uploaden</button>
        </form>
    </div>

@endsection
