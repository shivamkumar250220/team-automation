@extends('layouts.app')

@section('title', 'Post Templates — ' . $client->name)
@section('page_header', 'Post Templates')
@section('page_icon', 'mdi mdi-post-outline')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('gmb.clients.show', $client) }}">{{ $client->name }}</a></li>
    <li class="breadcrumb-item active">Post Templates</li>
@endsection

@section('content')

@foreach(['success', 'error', 'warning'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show">
            {{ session($type) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endforeach

<div class="row g-3">

    {{-- Header --}}
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('gmb.clients.show', $client) }}" class="btn btn-sm btn-light">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
            <button class="btn btn-success btn-sm" id="pullBtn" onclick="fetchCompetitors()">
                <i class="mdi mdi-refresh me-1"></i> Pull Competitors
            </button>
        </div>
    </div>

    {{-- Step 1: Competitor Analysis --}}
    <div class="col-12">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill" style="background:#1d4ed8; font-size:.7rem;">Step 1</span>
                    <h6 class="fw-semibold mb-0">Competitor Analysis</h6>
                </div>
                <p class="text-muted small mb-3">
                    Auto-fetches top 5 competitors for <strong>{{ $client->industry }}</strong> in <strong>{{ $client->city ?? 'your city' }}</strong> and pulls their recent GMB posts.
                </p>

                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:.875rem;">
                        <thead>
                            <tr style="font-size:.72rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#8a9ab5;">
                                <th class="border-0 pb-2">Name</th>
                                <th class="border-0 pb-2">Rating</th>
                                <th class="border-0 pb-2">Reviews</th>
                                <th class="border-0 pb-2">Photos</th>
                                <th class="border-0 pb-2">Rating Gap</th>
                                <th class="border-0 pb-2">Review Gap</th>
                                <th class="border-0 pb-2">Recent Posts</th>
                                <th class="border-0 pb-2">Last Pulled</th>
                            </tr>
                        </thead>
                        <tbody id="competitorTableBody">
                            <tr>
                                <td class="border-0 py-3">
                                    <span class="fw-semibold">{{ $client->name }}</span>
                                    <span class="badge ms-2" style="background:#1d4ed8; font-size:.7rem;">You</span>
                                </td>
                                <td class="border-0 py-3 text-muted" colspan="7">&mdash;</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="competitorLoading" class="text-center py-4 d-none">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2 text-muted small">Fetching competitors &amp; their GMB posts…</span>
                </div>

                <div id="competitorError" class="alert alert-danger py-2 mt-2 d-none mb-0 small"></div>

                <div id="pulledAt" class="text-muted mt-2 d-none" style="font-size:.75rem;">
                    <i class="mdi mdi-clock-outline me-1"></i>Last pulled: <span id="pulledAtText"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 2: Generate Post --}}
    <div class="col-12">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge rounded-pill" style="background:#1d4ed8; font-size:.7rem;">Step 2</span>
                            <h6 class="fw-semibold mb-0">Generate Post</h6>
                        </div>
                        <p class="text-muted small mb-0">AI analyses competitor posts and crafts a post designed to outrank them</p>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" type="button"
                            data-bs-toggle="collapse" data-bs-target="#postFormCollapse" id="createPostToggle">
                        <i class="mdi mdi-chevron-down me-1"></i> Create New Post
                    </button>
                </div>

                <div class="collapse" id="postFormCollapse">
                    <div class="border rounded p-3 bg-light">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label small" style="font-size:.72rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#8a9ab5;">Post Type</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pt_post_type" id="pt_type_new" value="whats_new" checked>
                                        <label class="form-check-label small" for="pt_type_new"><i class="mdi mdi-new-box me-1"></i>What's New</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pt_post_type" id="pt_type_offer" value="offer">
                                        <label class="form-check-label small" for="pt_type_offer"><i class="mdi mdi-tag me-1"></i>Offer</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pt_post_type" id="pt_type_event" value="event">
                                        <label class="form-check-label small" for="pt_type_event"><i class="mdi mdi-calendar me-1"></i>Event</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label small" style="font-size:.72rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#8a9ab5;">Topic / Theme</label>
                                <input type="text" id="pt_topic" class="form-control form-control-sm" placeholder="e.g. IVF Success Rates, Skin Whitening">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label small" style="font-size:.72rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#8a9ab5;">Emotion / Tone</label>
                                <select id="pt_emotion" class="form-select form-select-sm">
                                    <option value="trust and care">Trust &amp; Care</option>
                                    <option value="urgency and excitement">Urgency &amp; Excitement</option>
                                    <option value="empathy and hope">Empathy &amp; Hope</option>
                                    <option value="confidence and authority">Confidence &amp; Authority</option>
                                    <option value="warmth and community">Warmth &amp; Community</option>
                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label small" style="font-size:.72rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#8a9ab5;">Unique Selling Point (USP)</label>
                                <input type="text" id="pt_usp" class="form-control form-control-sm" placeholder="e.g. 15+ years experience, 98% success rate">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label small" style="font-size:.72rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#8a9ab5;">Call To Action</label>
                                <input type="text" id="pt_cta" class="form-control form-control-sm" placeholder="e.g. Book a free consultation today">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label small" style="font-size:.72rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#8a9ab5;">
                                    Special Offer <span class="text-muted fw-normal">(optional)</span>
                                </label>
                                <input type="text" id="pt_offer" class="form-control form-control-sm" placeholder="e.g. Free consultation this week">
                            </div>

                            <div class="col-12">
                                <button class="btn btn-success" id="generatePostBtn" onclick="generatePost()">
                                    <i class="mdi mdi-robot-outline me-1"></i> Generate Post
                                </button>
                            </div>

                            <div id="postLoading" class="col-12 text-center py-3 d-none">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted small mb-0">AI is analysing competitor posts and crafting yours…</p>
                            </div>

                            <div id="postResult" class="col-12 d-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0 fw-semibold small">Generated Post</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <small id="charCount" class="text-muted">0 / 1500</small>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="generatePost()">
                                            <i class="mdi mdi-refresh me-1"></i> Regenerate
                                        </button>
                                    </div>
                                </div>

                                <textarea id="generatedPost" class="form-control mb-3" rows="10"
                                          style="font-size:.875rem; line-height:1.6; resize:vertical;"
                                          oninput="updateCharCount()"></textarea>

                                <form method="POST" action="{{ route('gmb.post-templates.store', $client) }}" id="savePostForm">
                                    @csrf
                                    <input type="hidden" name="post_content"    id="save_post_content">
                                    <input type="hidden" name="post_type"       id="save_post_type">
                                    <input type="hidden" name="topic"           id="save_topic">
                                    <input type="hidden" name="emotion"         id="save_emotion">
                                    <input type="hidden" name="cta"             id="save_cta">
                                    <input type="hidden" name="usp"             id="save_usp">
                                    <input type="hidden" name="offer"           id="save_offer">
                                    <input type="hidden" name="competitors"     id="save_competitors">
                                    <input type="hidden" name="status"          id="save_status" value="draft">
                                    <input type="hidden" name="gmb_location_id" id="save_location_id">

                                    <div id="locationSelectWrap" class="mb-3 d-none">
                                        <label class="form-label small" style="font-size:.72rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#8a9ab5;">Publish to Location</label>
                                        <select id="save_location_select" class="form-select form-select-sm">
                                            <option value="">— Select Location —</option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}">{{ $loc->location_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary" onclick="savePost('draft')">
                                            <i class="mdi mdi-content-save me-1"></i> Save Draft
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="savePost('published')">
                                            <i class="mdi mdi-send me-1"></i> Publish to GMB
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 3: Saved Posts --}}
    <div class="col-12">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge rounded-pill" style="background:#1d4ed8; font-size:.7rem;">Step 3</span>
                    <h6 class="fw-semibold mb-0">Saved Posts</h6>
                </div>

                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:.875rem;">
                        <thead>
                            <tr style="font-size:.72rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#8a9ab5;">
                                <th class="border-0 pb-2">Status</th>
                                <th class="border-0 pb-2">Topic</th>
                                <th class="border-0 pb-2">Preview</th>
                                <th class="border-0 pb-2">Offer</th>
                                <th class="border-0 pb-2">Date</th>
                                <th class="border-0 pb-2" width="180">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($posts as $post)
                                <tr>
                                    <td class="py-3">
                                        <span class="badge bg-{{ $post->isPublished() ? 'success' : 'secondary' }}">
                                            {{ ucfirst($post->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 small">
                                        {{ $post->topic ?? '—' }}
                                        @if($post->emotion)
                                            <br><span class="text-muted" style="font-size:.78rem;">{{ $post->emotion }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-muted small">{{ Str::limit($post->post_content, 80) }}</td>
                                    <td class="py-3 small">
                                        @if($post->offer)
                                            <span class="text-success"><i class="mdi mdi-tag me-1"></i>{{ $post->offer }}</span>
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-muted small">{{ $post->created_at->format('d M Y') }}</td>
                                    <td class="py-3">
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#view-post-{{ $post->id }}">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            @if(!$post->isPublished())
                                                <button class="btn btn-sm btn-success" data-bs-toggle="collapse" data-bs-target="#publish-post-{{ $post->id }}">
                                                    <i class="mdi mdi-send me-1"></i> Publish
                                                </button>
                                            @endif
                                            <form method="POST" action="{{ route('gmb.post-templates.destroy', [$client, $post]) }}" onsubmit="return confirm('Delete this post?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light text-danger">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="6" class="p-0 border-0">
                                        <div class="collapse" id="view-post-{{ $post->id }}">
                                            <div class="p-3 bg-light border-bottom">
                                                <p class="small fw-semibold mb-2"><i class="mdi mdi-post-outline me-1"></i>Full Post Content</p>
                                                <textarea class="form-control form-control-sm mb-2" rows="6" style="font-size:.875rem; line-height:1.6;" readonly>{{ $post->post_content }}</textarea>
                                                <button class="btn btn-sm btn-outline-secondary" onclick="copyText(this)" data-text="{{ $post->post_content }}">
                                                    <i class="mdi mdi-content-copy me-1"></i> Copy
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                @if(!$post->isPublished())
                                    <tr>
                                        <td colspan="6" class="p-0 border-0">
                                            <div class="collapse" id="publish-post-{{ $post->id }}">
                                                <div class="p-3 bg-light border-bottom">
                                                    <form method="POST" action="{{ route('gmb.post-templates.publish', [$client, $post]) }}">
                                                        @csrf
                                                        <p class="small fw-semibold mb-2"><i class="mdi mdi-send me-1"></i>Select location to publish</p>
                                                        <div class="d-flex gap-2 align-items-end">
                                                            <div class="flex-grow-1">
                                                                <select name="gmb_location_id" class="form-select form-select-sm" required>
                                                                    <option value="">— Select Location —</option>
                                                                    @foreach($locations as $loc)
                                                                        <option value="{{ $loc->id }}">{{ $loc->location_name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="mdi mdi-send me-1"></i> Publish Now
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif

                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="mdi mdi-post-outline d-block mb-1" style="font-size:2rem; opacity:.35;"></i>
                                        <span class="small">No posts yet — pull competitors above to get started.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
const FETCH_URL = "{{ route('gmb.post-templates.fetch-competitors', $client) }}";
const GEN_URL   = "{{ route('gmb.post-templates.generate', $client) }}";
const CSRF      = "{{ csrf_token() }}";
const CLIENT    = "{{ $client->name }}";
const INDUSTRY  = "{{ $client->industry }}";

let selectedCompetitors  = [];
let topCompetitorRating  = 0;
let topCompetitorReviews = 0;

async function fetchCompetitors() {
    const btn  = document.getElementById('pullBtn');
    const load = document.getElementById('competitorLoading');
    const err  = document.getElementById('competitorError');

    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Pulling…';
    load.classList.remove('d-none');
    err.classList.add('d-none');

    console.log('[PostTemplate] fetchCompetitors — calling', FETCH_URL);

    try {
        const res  = await fetch(FETCH_URL, { headers: { 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();

        console.log('[PostTemplate] fetchCompetitors — response', data);

        load.classList.add('d-none');

        if (data.error) {
            err.textContent = data.error;
            err.classList.remove('d-none');
            return;
        }

        if (!data.competitors || data.competitors.length === 0) {
            err.textContent = 'No competitors found. Try updating the client city or industry.';
            err.classList.remove('d-none');
            return;
        }

        selectedCompetitors  = data.competitors;
        topCompetitorRating  = data.competitors[0]?.rating        ?? 0;
        topCompetitorReviews = data.competitors[0]?.total_reviews ?? 0;

        renderCompetitorTable(data);

        document.getElementById('pulledAt').classList.remove('d-none');
        document.getElementById('pulledAtText').textContent = data.pulled_at;

        const collapse = document.getElementById('postFormCollapse');
        if (collapse && !collapse.classList.contains('show')) {
            new bootstrap.Collapse(collapse, { show: true });
        }

    } catch (e) {
        console.error('[PostTemplate] fetchCompetitors — error', e);
        load.classList.add('d-none');
        err.textContent = 'Request failed: ' + e.message;
        err.classList.remove('d-none');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="mdi mdi-refresh me-1"></i> Pull Competitors';
    }
}

function renderCompetitorTable(data) {
    const tbody = document.getElementById('competitorTableBody');

    const clientRow = `
        <tr>
            <td class="py-3">
                <span class="fw-semibold">${CLIENT}</span>
                <span class="badge ms-2" style="background:#1d4ed8;font-size:.7rem;">You</span>
            </td>
            <td class="py-3 text-muted" colspan="6">&mdash;</td>
            <td class="py-3 text-muted small">${data.pulled_at}</td>
        </tr>
    `;

    const competitorRows = data.competitors.map((c, i) => {
        const ratingGap   = topCompetitorRating  - (c.rating        ?? 0);
        const reviewGap   = topCompetitorReviews - (c.total_reviews ?? 0);
        const ratingColor = ratingGap > 0 ? 'text-danger' : 'text-success';
        const reviewColor = reviewGap > 0 ? 'text-danger' : 'text-success';
        const ratingStr   = ratingGap === 0 ? '—' : (ratingGap > 0 ? `−${ratingGap.toFixed(1)}`            : `+${Math.abs(ratingGap).toFixed(1)}`);
        const reviewStr   = reviewGap === 0 ? '—' : (reviewGap > 0 ? `−${reviewGap.toLocaleString()}`      : `+${Math.abs(reviewGap).toLocaleString()}`);

        const postsHtml = (c.recent_posts && c.recent_posts.length > 0)
            ? `<span class="badge bg-success-subtle text-success" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#cposts-${i}">
                   ${c.recent_posts.length} posts ▾
               </span>
               <div class="collapse mt-1" id="cposts-${i}">
                   ${c.recent_posts.map(p => `<div class="text-muted small border rounded p-1 mb-1" style="font-size:.72rem;max-width:260px;">${p.summary || '(no text)'}</div>`).join('')}
               </div>`
            : '<span class="text-muted small">None found</span>';

        return `
            <tr>
                <td class="py-3 small fw-medium">${i + 1}. ${c.name}</td>
                <td class="py-3 small">
                    <span class="text-warning">${'★'.repeat(Math.round(c.rating ?? 0))}</span>
                    <span class="ms-1">${c.rating ?? '—'}</span>
                </td>
                <td class="py-3 small">${Number(c.total_reviews ?? 0).toLocaleString()}</td>
                <td class="py-3 small">${c.photos ?? '—'}</td>
                <td class="py-3 small ${ratingColor} fw-medium">${ratingStr}</td>
                <td class="py-3 small ${reviewColor} fw-medium">${reviewStr}</td>
                <td class="py-3 small">${postsHtml}</td>
                <td class="py-3 text-muted small">${data.pulled_at}</td>
            </tr>
        `;
    }).join('');

    tbody.innerHTML = clientRow + competitorRows;
}

async function generatePost() {
    if (selectedCompetitors.length === 0) {
        alert('Please pull competitor data first.');
        return;
    }

    const btn = document.getElementById('generatePostBtn');
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating…';

    document.getElementById('postResult').classList.add('d-none');
    document.getElementById('postLoading').classList.remove('d-none');

    const payload = {
        competitors: selectedCompetitors,
        post_type:   document.querySelector('input[name="pt_post_type"]:checked')?.value ?? 'whats_new',
        topic:       document.getElementById('pt_topic').value,
        emotion:     document.getElementById('pt_emotion').value,
        cta:         document.getElementById('pt_cta').value,
        usp:         document.getElementById('pt_usp').value,
        offer:       document.getElementById('pt_offer').value,
    };

    console.log('[PostTemplate] generatePost — payload', payload);

    try {
        const res  = await fetch(GEN_URL, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body:    JSON.stringify(payload),
        });
        const data = await res.json();

        console.log('[PostTemplate] generatePost — response', data);

        document.getElementById('postLoading').classList.add('d-none');

        if (data.error) {
            alert('Generation failed: ' + data.error);
            return;
        }

        document.getElementById('generatedPost').value = data.post;
        document.getElementById('postResult').classList.remove('d-none');
        updateCharCount();

    } catch (e) {
        console.error('[PostTemplate] generatePost — error', e);
        document.getElementById('postLoading').classList.add('d-none');
        alert('Request failed: ' + e.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="mdi mdi-robot-outline me-1"></i> Generate Post';
    }
}

function updateCharCount() {
    const len = document.getElementById('generatedPost').value.length;
    const el  = document.getElementById('charCount');
    el.textContent = `${len} / 1500`;
    el.className   = len > 1500 ? 'text-danger small' : 'text-muted small';
}

function savePost(status) {
    const content = document.getElementById('generatedPost').value.trim();
    if (!content) { alert('No post content to save.'); return; }

    const postType = document.querySelector('input[name="pt_post_type"]:checked')?.value ?? 'whats_new';

    document.getElementById('save_post_content').value = content;
    document.getElementById('save_post_type').value    = postType;
    document.getElementById('save_topic').value        = document.getElementById('pt_topic').value;
    document.getElementById('save_emotion').value      = document.getElementById('pt_emotion').value;
    document.getElementById('save_cta').value          = document.getElementById('pt_cta').value;
    document.getElementById('save_usp').value          = document.getElementById('pt_usp').value;
    document.getElementById('save_offer').value        = document.getElementById('pt_offer').value;
    document.getElementById('save_competitors').value  = JSON.stringify(selectedCompetitors);
    document.getElementById('save_status').value       = status;

    const locWrap = document.getElementById('locationSelectWrap');

    if (status === 'published') {
        locWrap.classList.remove('d-none');
        const locVal = document.getElementById('save_location_select').value;
        document.getElementById('save_location_id').value = locVal;
        if (!locVal) { alert('Please select a location to publish to.'); return; }
    } else {
        locWrap.classList.add('d-none');
    }

    console.log('[PostTemplate] savePost — submitting', { status, postType, content_length: content.length });

    document.getElementById('savePostForm').submit();
}

function copyText(btn) {
    navigator.clipboard.writeText(btn.getAttribute('data-text')).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="mdi mdi-check me-1"></i> Copied!';
        setTimeout(() => { btn.innerHTML = orig; }, 1800);
    });
}
</script>
@endpush