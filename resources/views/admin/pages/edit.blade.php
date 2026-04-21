@extends('layouts.admin.app', ['page' => 'CMS Pages'])

@section('title', 'Edit ' . $page->title)

@section('header_data')
    @php
        $page_title = 'Edit ' . $page->title;
        $page_pretitle = 'CMS Pages';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'CMS Pages', 'url' => route('admin.pages.index')],
        ['title' => 'Edit', 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="row">
        <div class="col-lg-8 col-md-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Content: {{ $page->title }}</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.pages.update', $page) }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label required">Page Title</label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $page->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" value="{{ $page->slug }}" disabled>
                            <small class="form-hint">Slug cannot be changed for core system pages.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Page Content (Block editor)</label>
                            <div id="block-editor" class="border rounded p-2" style="min-height:250px; background:#fff"></div>
                            <input type="hidden" name="content_blocks" id="content_blocks_input" value='{{ old('content_blocks', json_encode($page->content_blocks ?? [])) }}'>
                            <div class="mt-2">
                                <button type="button" id="add-paragraph" class="btn btn-sm btn-outline-secondary">Add Paragraph</button>
                                <button type="button" id="add-heading" class="btn btn-sm btn-outline-secondary">Add Heading</button>
                                <button type="button" id="add-image" class="btn btn-sm btn-outline-secondary">Add Image</button>
                            </div>
                            @error('content_blocks')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <fieldset class="form-fieldset mt-4">
                            <h4 class="mb-3">SEO Settings (Optional)</h4>
                            <div class="mb-3">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $page->meta_title) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta Description</label>
                                <textarea name="meta_description" class="form-control" rows="3">{{ old('meta_description', $page->meta_description) }}</textarea>
                            </div>
                        </fieldset>

                        <div class="form-footer text-end mt-4">
                            <a href="{{ route('admin.pages.index') }}" class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                    <script>
                        (function(){
                            const editor = document.getElementById('block-editor');
                            const hidden = document.getElementById('content_blocks_input');

                            function renderBlocks(blocks){
                                editor.innerHTML = '';
                                blocks.forEach((b, idx)=>{
                                    const wrapper = document.createElement('div');
                                    wrapper.className = 'mb-3 p-2 border rounded';
                                    const removeBtn = document.createElement('button');
                                    removeBtn.type = 'button';
                                    removeBtn.className = 'btn btn-sm btn-danger float-end';
                                    removeBtn.textContent = 'Remove';
                                    removeBtn.onclick = ()=>{ blocks.splice(idx,1); renderBlocks(blocks); saveBlocks(blocks); };
                                    if(b.type === 'heading'){
                                        const h = document.createElement('input');
                                        h.className = 'form-control mb-2';
                                        h.value = b.data?.text || '';
                                        h.oninput = ()=>{ b.data = { text: h.value }; saveBlocks(blocks); };
                                        wrapper.appendChild(removeBtn);
                                        wrapper.appendChild(h);
                                    } else if(b.type === 'image'){
                                        const img = document.createElement('img');
                                        img.src = b.data?.url || '';
                                        img.className = 'img-fluid mb-2';
                                        img.style.maxWidth = '100%';
                                        wrapper.appendChild(removeBtn);
                                        wrapper.appendChild(img);
                                    } else {
                                        const ta = document.createElement('textarea');
                                        ta.className = 'form-control mb-2';
                                        ta.rows = 4;
                                        ta.value = b.data?.text || '';
                                        ta.oninput = ()=>{ b.data = { text: ta.value }; saveBlocks(blocks); };
                                        wrapper.appendChild(removeBtn);
                                        wrapper.appendChild(ta);
                                    }
                                    editor.appendChild(wrapper);
                                });
                                if(blocks.length === 0){
                                    editor.innerHTML = '<div class="text-muted">No blocks yet — add one.</div>';
                                }
                            }

                            function saveBlocks(blocks){
                                hidden.value = JSON.stringify(blocks);
                            }

                            // init
                            let initial = [];
                            try { initial = JSON.parse(hidden.value || '[]'); } catch(e){ initial = [] }
                            // fallback: if no blocks but old content exists, create a paragraph block
                            if(initial.length === 0){
                                const old = `{!! addslashes(old('content', $page->content ?? '')) !!}`.trim();
                                if(old){ initial = [{ type:'paragraph', data:{ text: old } }]; }
                            }

                            renderBlocks(initial);

                            document.getElementById('add-paragraph').addEventListener('click', ()=>{ initial.push({type:'paragraph', data:{ text: '' }}); renderBlocks(initial); saveBlocks(initial); });
                            document.getElementById('add-heading').addEventListener('click', ()=>{ initial.push({type:'heading', data:{ text: '' }}); renderBlocks(initial); saveBlocks(initial); });
                            document.getElementById('add-image').addEventListener('click', async ()=>{
                                const fileInput = document.createElement('input');
                                fileInput.type = 'file';
                                fileInput.accept = 'image/*';
                                fileInput.onchange = async () => {
                                    const file = fileInput.files[0];
                                    if(!file) return;
                                    const form = new FormData();
                                    form.append('file', file);
                                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                                    const resp = await fetch("{{ url('/admin/pages') }}/{{ $page->id }}/media", {
                                        method: 'POST',
                                        headers: token ? { 'X-CSRF-TOKEN': token } : {},
                                        body: form
                                    });
                                    if(resp.ok){
                                        const json = await resp.json();
                                        if(json.success){
                                            initial.push({ type:'image', data:{ url: json.url, media_id: json.media_id } });
                                            renderBlocks(initial); saveBlocks(initial);
                                            return;
                                        }
                                    }
                                    alert('Upload failed');
                                };
                                fileInput.click();
                            });

                            // ensure hidden input updated before submit
                            const form = editor.closest('form');
                            form.addEventListener('submit', ()=> saveBlocks(initial));
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
@endsection
