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
            |
            <a href="{{ route('logout') }}">Uitloggen</a>
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
            <input type="text" name="source_path" id="source_path" value="{{ $sourcePath }}" class="form-control"
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


    {{-- Bulk update form --}}
    <form method="post" action="{{ route('mapping.update') }}" id="bulk-form">
        @csrf
        <input type="hidden" id="mapping_source_path" name="source_path" value="{{ $sourcePath }}">
        <input type="hidden" id="mapping_destination_path" name="destination_path" value="{{ $destinationPath }}">
        <input type="hidden" id="mapping_status" name="status" value="{{ $status }}">

        <div class="row">
            <div class="col-md-10">
                <label class="form-label">{{$total}} bronpaden gevonden. Wat moet het doelpad worden?</label>
                <br>
                <select
                    name="dest_dir_id"
                    id="dest_dir_id"
                    data-live-search="true"
                    class="form-select"
                >
                    <option value="">--selecteer een doel pad--</option>
                    <option value="-">Negeren, niet importeren</option>
                    @foreach($destinationsDirs as $dir)
                        <option value="{{ $dir->id }}">{{ $dir->path }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2" style="padding-top: 30px">
                <button class="btn btn-success w-100" type="button"
                        onclick="
                               if (document.getElementById('dest_dir_id').value.trim() === '') {
                                    if (confirm('Het doel pad is leeg. Weet je zeker dat je dit wilt bijwerken?')) {
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


    <script>
        /* ===== Vanilla JS: select → searchable combobox (injects HTML+CSS) ===== */

        (function () {
            // Inject CSS once
            const CSS_ID = "vanilla-combobox-styles";
            if (!document.getElementById(CSS_ID)) {
                const style = document.createElement("style");
                style.id = CSS_ID;
                style.textContent = `
      .vcb-wrap{position:relative;display:inline-block;min-width:240px; flex:1; display:flex; }
      .vcb-input{width:100%;box-sizing:border-box;padding:.5rem .75rem;border:1px solid #ccc;border-radius:.5rem;outline:none}
      .vcb-input:focus{border-color:#4096ff;box-shadow:0 0 0 3px rgba(64,150,255,.15)}
      .vcb-list{position:absolute;left:0;right:0;top:calc(100% + 4px);z-index:1000;
        background:#fff;border:1px solid #ddd;border-radius:.5rem;max-height:220px;overflow:auto;margin:0;padding:.25rem 0;list-style:none;display:none}
      .vcb-list.vcb-open{display:block}
      .vcb-item{padding:.45rem .75rem;cursor:pointer;line-height:1.25}
      .vcb-item[aria-selected="true"], .vcb-item:hover{background:#f5f5f5}
      .vcb-empty{padding:.45rem .75rem;color:#888}
      .vcb-clear{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:transparent;cursor:pointer;font-size:14px;color:#999}
      .vcb-clear:hover{color:#666}
      .vcb-mark{font-weight:600}
      .vcb-sr{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
    `;
                document.head.appendChild(style);
            }

            function makeSearchableSelect(select, {placeholder = "Typ om te zoeken..."} = {}) {
                if (!(select instanceof HTMLSelectElement)) return;

                // Build option model from the original select
                const opts = Array.from(select.options).map(o => ({value: o.value, label: o.text}));

                // Wrapper + input + clear + list
                const wrap = document.createElement("div");
                wrap.className = "vcb-wrap";
                const input = document.createElement("input");
                input.className = "vcb-input";
                input.type = "text";
                input.setAttribute("role", "combobox");
                input.setAttribute("aria-autocomplete", "list");
                input.setAttribute("aria-expanded", "false");
                input.setAttribute("aria-haspopup", "listbox");
                input.placeholder = placeholder;

                const clearBtn = document.createElement("button");
                clearBtn.type = "button";
                clearBtn.className = "vcb-clear";
                clearBtn.setAttribute("aria-label", "Wissen");
                clearBtn.textContent = "×";

                const list = document.createElement("ul");
                list.className = "vcb-list";
                list.setAttribute("role", "listbox");

                wrap.appendChild(input);
                wrap.appendChild(clearBtn);
                wrap.appendChild(list);

                // Hide original select, keep it in DOM for form submit
                select.style.display = "none";
                select.parentNode.insertBefore(wrap, select.nextSibling);

                // State
                let filtered = opts.slice();
                let activeIndex = -1;
                const openList = () => {
                    list.classList.add("vcb-open");
                    input.setAttribute("aria-expanded", "true");
                };
                const closeList = () => {
                    list.classList.remove("vcb-open");
                    input.setAttribute("aria-expanded", "false");
                    activeIndex = -1;
                };
                const setSelectValue = (val) => {
                    select.value = val;
                };

                // If select had a preselected value, reflect it in the input
                const pre = select.value;
                if (pre) {
                    const m = opts.find(o => o.value === pre);
                    if (m) input.value = m.label;
                }

                function escapeRegExp(s) {
                    return s.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
                }

                function renderList(items, query) {
                    list.innerHTML = "";
                    if (!items.length) {
                        const li = document.createElement("li");
                        li.className = "vcb-empty";
                        li.textContent = "Geen resultaten";
                        list.appendChild(li);
                        return;
                    }

                    const qf = fold(query);

                    items.forEach((opt, idx) => {
                        const li = document.createElement("li");
                        li.className = "vcb-item";
                        li.setAttribute("role", "option");
                        li.setAttribute("data-value", opt.value);

                        if (qf) {
                            const lf = fold(opt.label);
                            const i = lf.indexOf(qf);
                            if (i >= 0) {
                                // Highlight door te slicen op karakterbasis (graphemes)
                                const chars = Array.from(opt.label);
                                const before = chars.slice(0, i).join('');
                                const mid    = chars.slice(i, i + qf.length).join('');
                                const after  = chars.slice(i + qf.length).join('');
                                li.innerHTML = `${before}<span class="vcb-mark">${mid}</span>${after}`;
                            } else {
                                li.textContent = opt.label;
                            }
                        } else {
                            li.textContent = opt.label;
                        }

                        li.addEventListener("mousedown", (e) => {
                            e.preventDefault();
                            choose(idx);
                        });
                        list.appendChild(li);
                    });
                }

                // 1) Helper bovenaan bij je andere functies
                function fold(s) {
                    // Unicode-normaliseren en alle combining marks verwijderen
                    return s.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
                }

                function filterItems(q) {
                    const t = fold(q.trim());
                    if (!t) return opts.slice();
                    return opts.filter(o => fold(o.label).includes(t));
                }

                function choose(idx) {
                    const item = filtered[idx];
                    if (!item) return;
                    input.value = item.label;
                    setSelectValue(item.value);
                    closeList();
                    input.focus();
                }

                function updateActive(nextIndex) {
                    const items = Array.from(list.querySelectorAll(".vcb-item"));
                    if (!items.length) return;
                    activeIndex = Math.max(0, Math.min(nextIndex, items.length - 1));
                    items.forEach((el, i) => el.setAttribute("aria-selected", i === activeIndex ? "true" : "false"));
                    // Scroll into view
                    const activeEl = items[activeIndex];
                    if (activeEl) {
                        const {offsetTop, offsetHeight} = activeEl;
                        const {scrollTop, clientHeight} = list;
                        if (offsetTop < scrollTop) list.scrollTop = offsetTop;
                        else if (offsetTop + offsetHeight > scrollTop + clientHeight) list.scrollTop = offsetTop + offsetHeight - clientHeight;
                    }
                }

                // Events
                input.addEventListener("input", () => {
                    setSelectValue(""); // reset until user chooses
                    filtered = filterItems(input.value);
                    renderList(filtered, input.value);
                    openList();
                    activeIndex = -1;
                });

                input.addEventListener("focus", () => {
                    filtered = filterItems(input.value);
                    renderList(filtered, input.value);
                    if (filtered.length) openList();
                });

                input.addEventListener("keydown", (e) => {
                    const KEY = e.key;
                    if (KEY === "ArrowDown") {
                        e.preventDefault();
                        if (!list.classList.contains("vcb-open")) {
                            filtered = filterItems(input.value);
                            renderList(filtered, input.value);
                            openList();
                        }
                        updateActive(activeIndex + 1);
                    } else if (KEY === "ArrowUp") {
                        e.preventDefault();
                        if (list.classList.contains("vcb-open")) updateActive(activeIndex - 1);
                    } else if (KEY === "Enter") {
                        if (list.classList.contains("vcb-open") && activeIndex >= 0) {
                            e.preventDefault();
                            choose(activeIndex);
                        }
                    } else if (KEY === "Escape") {
                        if (list.classList.contains("vcb-open")) {
                            e.preventDefault();
                            closeList();
                        } else {
                            input.select();
                        }
                    }
                });

                clearBtn.addEventListener("click", () => {
                    input.value = "";
                    setSelectValue("");
                    filtered = opts.slice();
                    renderList(filtered, "");
                    openList();
                    input.focus();
                });

                document.addEventListener("click", (e) => {
                    if (!wrap.contains(e.target)) closeList();
                });

                // Resize list to input width (in case of responsive layout)
                const ro = new ResizeObserver(() => {
                    list.style.minWidth = wrap.clientWidth + "px";
                });
                ro.observe(wrap);
            }

            // Expose globally
            window.makeSearchableSelect = makeSearchableSelect;
        })();

        // ==== Initialiseren op jouw select ====
        document.addEventListener("DOMContentLoaded", function () {
            const sel = document.getElementById("dest_dir_id");
            if (sel) makeSearchableSelect(sel, {placeholder: "--selecteer een doel pad--"});
        });
    </script>

@endsection
