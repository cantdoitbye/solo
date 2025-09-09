{{-- resources/views/admin/feedbacks/index.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Feedbacks')
@section('subtitle', 'User feedback and suggestions')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-comments me-2"></i>User Feedbacks</h5>
            </div>
            <div class="card-body">
                @if($feedbacks->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Title</th>
                                    <th>Email</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($feedbacks as $feedback)
                                    <tr>
                                        <td>
                                            <strong>{{ $feedback->user_name }}</strong>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $feedback->title }}</div>
                                            <small class="text-muted">{{ Str::limit($feedback->message, 50) }}</small>
                                        </td>
                                        <td>{{ $feedback->email }}</td>
                                        <td>
                                            <small>{{ $feedback->created_at->format('M d, Y') }}</small><br>
                                            <small class="text-muted">{{ $feedback->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.feedbacks.show', $feedback->id) }}" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <form method="POST" 
                                                      action="{{ route('admin.feedbacks.destroy', $feedback->id) }}" 
                                                      style="display: inline-block;"
                                                      onsubmit="return confirm('Are you sure you want to delete this feedback?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $feedbacks->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Feedbacks Yet</h5>
                        <p class="text-muted">User feedbacks will appear here when submitted.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
</style>
@endpush