@extends('layouts.app')

@section('title', 'AI Review Drafts — ' . $client->name)
@section('page_header', 'AI Review Drafts')
@section('page_icon', 'mdi mdi-robot-outline')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('gmb.clients.show', $client) }}">{{ $client->name }}</a></li>
    <li class="breadcrumb-item active">AI Drafts</li>
@endsection

@section('content')

<div class="row g-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('gmb.clients.show', $client) }}" class="btn btn-sm btn-light">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
            <div class="d-flex gap-2">
                <a href="{{ route('gmb.reviews.create', $client) }}" class="btn btn-sm btn-primary">
                    <i class="mdi mdi-plus me-1"></i> Add Review Manually
                </a>
                <form method="POST" action="{{ route('gmb.reviews.pull', $client) }}">
                    @csrf
                    <button class="btn btn-sm btn-success">
                        <i class="mdi mdi-refresh me-1"></i> Pull Reviews & Generate Drafts
                    </button>
                </form>
            </div>
        </div>
    </div>

    @forelse($locations as $location)
        <div class="col-12">
            <div class="card mb-0">
                <div class="card-body">

                    <h6 class="card-title mb-1">
                        {{ $location->location_name }}
                        @if($location->city)
                            <span class="text-muted fw-normal small ms-1">— {{ $location->city }}</span>
                        @endif
                    </h6>
                    <p class="card-subtitle mb-3">{{ $location->reviews->count() }} reviews</p>

                    <table class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Reviewer</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th width="200">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($location->reviews as $review)
                                <tr>
                                    <td class="fw-medium">{{ $review->reviewer_name }}</td>
                                    <td>
                                        <span class="text-warning">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="mdi mdi-star{{ $i <= $review->starCount() ? '' : '-outline' }}"></i>
                                            @endfor
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        {{ $review->comment ? Str::limit($review->comment, 80) : '—' }}
                                    </td>
                                    <td class="text-muted small">
                                        {{ $review->review_time?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td>
                                        @if($review->reply_posted)
                                            <span class="badge bg-success">Replied</span>
                                        @elseif($review->draft?->final_response)
                                            <span class="badge bg-info">Draft Ready</span>
                                        @elseif($review->draft)
                                            <span class="badge bg-warning text-dark">Pick Draft</span>
                                        @elseif($review->isNegative())
                                            <span class="badge bg-danger">Needs Attention</span>
                                        @else
                                            <span class="badge bg-secondary">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$review->reply_posted && $review->draft)
                                            <button class="btn btn-sm btn-light"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#drafts-{{ $review->id }}">
                                                <i class="mdi mdi-pencil me-1"></i> View Drafts
                                            </button>
                                        @elseif($review->reply_posted)
                                            <span class="text-muted small">Done</span>
                                        @else
                                            <span class="text-muted small">Pull to generate</span>
                                        @endif
                                    </td>
                                </tr>

                                @if(!$review->reply_posted && $review->draft)
                                    <tr>
                                        <td colspan="6" class="p-0 border-0">
                                            <div class="collapse" id="drafts-{{ $review->id }}">
                                                <div class="p-3 bg-light border-bottom">

                                                    @if($review->reply_text)
                                                        <div class="alert alert-info py-2 mb-3">
                                                            <small class="fw-semibold d-block mb-1">
                                                                <i class="mdi mdi-reply me-1"></i>Existing Reply
                                                            </small>
                                                            <small>{{ $review->reply_text }}</small>
                                                        </div>
                                                    @endif

                                                    <p class="small fw-semibold mb-2">
                                                        <i class="mdi mdi-robot-outline me-1"></i>Pick a draft, edit if needed, then post:
                                                    </p>

                                                    <form method="POST" action="{{ route('gmb.reviews.select-draft', $review) }}">
                                                        @csrf

                                                        <ul class="nav nav-pills mb-2">
                                                            @foreach($review->draft->getDraftsArray() as $num => $text)
                                                                <li class="nav-item">
                                                                    <button class="nav-link py-1 px-2 small {{ $loop->first ? 'active' : '' }}"
                                                                            type="button"
                                                                            data-draft="{{ $num }}"
                                                                            data-text="{{ $text }}"
                                                                            onclick="selectDraft({{ $review->id }}, {{ $num }}, this)">
                                                                        Draft {{ $num }}
                                                                    </button>
                                                                </li>
                                                            @endforeach
                                                        </ul>

                                                        <input type="hidden" name="selected_draft"
                                                               id="selected_draft_{{ $review->id }}"
                                                               value="{{ array_key_first($review->draft->getDraftsArray()) }}">

                                                        <textarea name="final_response"
                                                                  id="final_response_{{ $review->id }}"
                                                                  class="form-control form-control-sm mb-2"
                                                                  rows="3">{{ array_values($review->draft->getDraftsArray())[0] ?? '' }}</textarea>

                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="mdi mdi-send me-1"></i> Post Reply to Google
                                                        </button>
                                                    </form>

                                                    @if($review->draft->final_response)
                                                        <div class="mt-3 p-2 bg-white rounded border">
                                                            <small class="fw-semibold d-block mb-1 text-success">
                                                                <i class="mdi mdi-check-circle me-1"></i>Last Saved Response
                                                            </small>
                                                            <small class="d-block text-muted">{{ $review->draft->final_response }}</small>
                                                        </div>
                                                    @endif

                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif

                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        No reviews yet — click Pull Reviews above
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-warning">No active locations found for this client.</div>
        </div>
    @endforelse

</div>

@endsection

@push('scripts')
<script>
function selectDraft(reviewId, draftNum, btn) {
    document.getElementById('selected_draft_' + reviewId).value = draftNum;
    document.getElementById('final_response_' + reviewId).value = btn.getAttribute('data-text');
    btn.closest('ul').querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');
}
</script>
@endpush