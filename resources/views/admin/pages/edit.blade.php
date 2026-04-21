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
                    <script src="https://unpkg.com/@tiptap/core@2.0.0-beta.79/dist/tiptap-core.umd.js"></script>
                    <script src="https://unpkg.com/@tiptap/starter-kit@2.0.0-beta.79/dist/tiptap-starter-kit.umd.js"></script>
                    <script src="https://unpkg.com/@tiptap/extension-image@2.0.0-beta.28/dist/tiptap-extension-image.umd.js"></script>
                    <script>
                        (function(){
                            const hidden = document.getElementById('content_blocks_input');
                            const editorEl = document.getElementById('block-editor');

                            // helper: convert existing simple blocks to HTML for editor initial content
                            function blocksToHtml(blocks){
                                if(!blocks || !blocks.length) return '';
                                return blocks.map(b=>{
                                    if(b.type === 'heading') return `<h2>${escapeHtml(b.data?.text||'')}</h2>`;
                                    if(b.type === 'image') return `<p><img src="${escapeHtml(b.data?.url||'')}"/></p>`;
                                    return `<p>${escapeHtml(b.data?.text||'')}</p>`;
                                }).join('');
                            }

                            function escapeHtml(unsafe){
                                return unsafe.replace(/[&<>\"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]; });
                            }

                            // initialize TipTap editor
                            const { Editor } = window['@tiptap/core'];
                            const StarterKit = window['@tiptap/starter-kit'].default;
                            const Image = window['@tiptap/extension-image'].default;

                            let initialHtml = '';
                            try {
                                const parsed = JSON.parse(hidden.value || '[]');
                                if(Array.isArray(parsed) && parsed.length) {
                                    initialHtml = blocksToHtml(parsed);
                                } else {
                                    initialHtml = `{!! addslashes(old('content', $page->content ?? '')) !!}`;
                                }
                            } catch (e){
                                initialHtml = `{!! addslashes(old('content', $page->content ?? '')) !!}`;
                            }

                            const editor = new Editor({
                                element: editorEl,
                                extensions: [StarterKit, Image],
                                content: initialHtml,
                            });

                            // image upload button
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
                                            editor.chain().focus().setImage({ src: json.url }).run();
                                            return;
                                        }
                                    }
                                    alert('Upload failed');
                                };
                                fileInput.click();
                            });

                            // on submit save editor JSON
                            const form = editorEl.closest('form');
                            form.addEventListener('submit', ()=>{
                                try {
                                    const doc = editor.getJSON();
                                    hidden.value = JSON.stringify(doc);
                                } catch(e){
                                    hidden.value = '';
                                }
                            });
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
@endsection
