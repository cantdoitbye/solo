@extends('admin.layouts.app')

@section('title', 'Users Management - Admin Panel')
@section('page-title', 'Users Management')

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.index', ['status' => 'active']) }}" 
           class="btn btn-sm {{ request('status') === 'active' ? 'btn-primary' : 'btn-outline-primary' }}">
            Active
        </a>
        <a href="{{ route('admin.users.index', ['status' => 'blocked']) }}" 
           class="btn btn-sm {{ request('status') === 'blocked' ? 'btn-danger' : 'btn-outline-danger' }}">
            Blocked
        </a>
        <a href="{{ route('admin.users.index', ['status' => 'deleted']) }}" 
           class="btn btn-sm {{ request('status') === 'deleted' ? 'btn-secondary' : 'btn-outline-secondary' }}">
            Deleted
        </a>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-header bg-white border-0">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0 fw-semibold">
                    <i class="fas fa-users me-2 text-primary"></i>All Users
                </h5>
            </div>
            <div class="col-md-6">
                <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex gap-2">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Search users..."
                           value="{{ request('search') }}">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.users.index', ['status' => request('status')]) }}" 
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="py-3">Contact</th>
                        <th class="py-3">Location</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Joined</th>
                        <th class="py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px;">
                                    <span class="text-white fw-bold">
                                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $user->name ?? 'N/A' }}</div>
                                    <small class="text-muted">ID: #{{ $user->id }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div>{{ $user->phone_number ?? 'N/A' }}</div>
                            @if($user->email)
                                <small class="text-muted">{{ $user->email }}</small>
                            @endif
                        </td>
                        <td class="py-3">
                            <div>{{ $user->city ?? 'N/A' }}</div>
                            @if($user->country)
                                <small class="text-muted">{{ $user->country }}</small>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($user->deleted_at)
                                <span class="status-badge bg-secondary text-white">Deleted</span>
                            @else
                                <span class="status-badge 
                                    @if($user->status === 'active') bg-success text-white
                                    @elseif($user->status === 'blocked') bg-danger text-white
                                    @else bg-warning text-dark @endif">
                                    {{ ucfirst($user->status) }}
                                </span>
                            @endif
                            @if($user->blocked_at)
                                <br><small class="text-muted">{{ $user->blocked_at->format('M d, Y') }}</small>
                            @endif
                        </td>
                        <td class="py-3">
                            <div>{{ $user->created_at->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                        </td>
                        <td class="py-3 text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.users.show', $user->id) }}" 
                                   class="btn btn-outline-primary" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @if(!$user->deleted_at)
                                    @if($user->status === 'active')
                                        <button type="button" 
                                                class="btn btn-outline-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#blockUserModal"
                                                data-user-id="{{ $user->id }}"
                                                data-user-name="{{ $user->name }}"
                                                title="Block User">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @elseif($user->status === 'blocked')
                                        <form method="POST" action="{{ route('admin.users.toggle-status', $user->id) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="action" value="unblock">
                                            <button type="submit" 
                                                    class="btn btn-outline-success"
                                                    title="Unblock User"
                                                    onclick="return confirm('Are you sure you want to unblock this user?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-outline-danger"
                                                title="Delete User"
                                                onclick="return confirmDelete('Are you sure you want to delete this user? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.restore', $user->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" 
                                                class="btn btn-outline-info"
                                                title="Restore User"
                                                onclick="return confirm('Are you sure you want to restore this user?')">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <div class="text-muted">
                                @if(request('search'))
                                    No users found for "{{ request('search') }}"
                                @else
                                    No users found
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($users->hasPages())
    <div class="card-footer bg-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results
            </div>
            {{ $users->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Block User Modal -->
<div class="modal fade" id="blockUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="blockUserForm">
                @csrf
                <input type="hidden" name="action" value="block">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-ban text-warning me-2"></i>Block User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Are you sure you want to block <strong id="userNameToBlock"></strong>?</p>
                    
                    <div class="mb-3">
                        <label for="blockReason" class="form-label">Reason for blocking <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="blockReason" 
                                  name="reason" 
                                  rows="3" 
                                  placeholder="Enter the reason for blocking this user..."
                                  required></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This will immediately log out the user and prevent them from accessing the application.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-ban me-2"></i>Block User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Handle block user modal
    document.getElementById('blockUserModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        const userName = button.getAttribute('data-user-name');
        
        document.getElementById('userNameToBlock').textContent = userName;
        document.getElementById('blockUserForm').action = `/admin/users/${userId}/toggle-status`;
    });
</script>
@endsection