@extends('layouts.app')

@section('title', 'Mapping')

@section('content')

    <div class="row">
        <div class="col-md-9">
            <h1 class="h4 mb-3 mt-2">Document Mapper ({{$percentageMapped}} % gemapped)</h1>
        </div>
        <div class="col-md-3 text-end">
            <a href="{{ route('mapping.import') }}">Nieuwe CSV importeren</a>
            |
            <a href="{{ route('mapping.download') }}"
                onclick="this.innerHTML='Bezig met downloaden...';
                "
            >Backup downloaden</a>
        </div>
    </div>



    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Search form --}}
    <form method="get" action="{{ route('mapping.index') }}" class="row">
        <div class="col-md-4">
            <label class="form-label">Zoek in <code>bron pad</code></label>
            <input type="text" name="source_path" value="{{ $sourcePath }}" class="form-control"
                   placeholder="bijvoorbeeld: /Samenstel/*/IB"
                   onkeyup="document.getElementById('mapping_source_path').value = this.value;"
            >
        </div>

        <div class="col-md-3">
            <label class="form-label">Zoek in <code>doel pad</code> (optioneel)</label>
            <input type="text" name="destination_path" id="destination_path"
                   value="{{ $destinationPath }}" class="form-control"
                   onkeyup="document.getElementById('mapping_destination_path').value = this.value;"
            >

        </div>

        <div class="col-md-3">
            <label class="form-label">Toon</label>
            <select name="status" class="form-select"
                    onchange="document.getElementById('mapping_status').value = this.value;"
            >
                <option value="all" @if($status === 'all') selected @endif>Alle</option>
                <option value="mapped" @if($status === 'mapped') selected @endif>Gemapt (doelpad gevuld)</option>
                <option value="unmapped" @if($status === 'unmapped') selected @endif>Niet gemapt (doelpad leeg)
                </option>
            </select>

        </div>


        <div class="col-md-2" style="padding-top: 32px">
            <button class="btn btn-primary w-100" type="submit">Zoek</button>
        </div>
    </form>
    <br>

    @if($sourcePath)
        {{-- Bulk update form --}}
        <form method="post" action="{{ route('mapping.update') }}" id="bulk-form">
            @csrf
            <input type="hidden" id="mapping_source_path" name="source_path" value="{{ $sourcePath }}">
            <input type="hidden" id="mapping_destination_path" name="destination_path" value="{{ $destinationPath }}">
            <input type="hidden" id="mapping_status" name="status" value="{{ $status }}">

            <div class="row">
                <div class="col-md-10">
                    <label class="form-label">{{$total}} bronpaden gevonden. Wat moet het doelpad worden. Zet doelpad op "-" om te negeren.</label>
                    <input type="text" name="dest_path" id="dest_path" class="form-control" placeholder="" required
                           onkeydown="if(event.key === 'Enter'){event.preventDefault(); return false;}">
                </div>

                <div class="col-md-2" style="padding-top: 32px">
                    <button class="btn btn-success w-100" type="button"
                            onclick="
                                if(document.getElementById('dest_path').value.trim() === '') {
                                    if(confirm('Het doel pad is leeg. Weet je zeker dat je dit wilt bijwerken?')) {
                                        document.getElementById('bulk-form').submit();
                                    } else {
                                        return;
                                    }
                                } else {
                                    document.getElementById('bulk-form').submit();
                                }

                            ">Bijwerken
                    </button>
                </div>
            </div>
            <br>
            @endif


            <div>
                {{ $rows->withQueryString()->links('pagination::bootstrap-5') }}
            </div>

            <div class="table-responsive bg-white border rounded">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr style="white-space: nowrap">
                        <th>Bron pad</th>
                        <th style="width:180px">Relatienummer</th>
                        <th>Doel pad</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr style="white-space: nowrap">
                            <td class="truncate"
                                style="max-width: 60vw; overflow: hidden; text-overflow: ellipsis;"
                                title="{{ $row->source_path }}">{{ $row->source_path }}</td>
                            <td>{{ $row->source_relation_number }}</td>
                            <td>{{ $row->destFormatted()}}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Geen resultaten</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <br>
            <div>
                {{ $rows->withQueryString()->links('pagination::bootstrap-5') }}
            </div>


        </form>


        @endsection
