@extends('layouts.app')

@section('title', 'Pending Invitation')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-envelope-exclamation text-warning" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="card-title mb-3">Welcome to ILA System</h2>
                    <p class="card-text text-muted mb-4">
                        Your account has been created successfully! However, as a staff member, you need to be invited to join a workspace before you can access the full system.
                    </p>
                    <div class="alert alert-info">
                        <strong>What happens next?</strong><br>
                        A lawyer or workspace owner will need to invite you to their workspace from the settings page. Once invited, you'll receive a notification and can accept the invitation to join.
                    </div>
                    <div class="mt-4">
                        <p class="text-muted">Check back later or contact your administrator for an invitation.</p>
                        <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#logoutModal">
                            <i class="bi bi-box-arrow-right mr-2"></i>Logout
                        </button>
                    </div>
                </div>
            </div>

            <div class="card shadow" id="pendingInvitationActions">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                        <div>
                            <h4 class="mb-1">Notifications</h4>
                            <p class="text-muted mb-0">Your workspace invitation and other account notices will appear here.</p>
                        </div>
                        <button type="button" class="btn btn-light btn-sm" id="refreshInvitationsBtn">
                            <i class="bi bi-arrow-repeat mr-1"></i>Refresh
                        </button>
                    </div>

                    <div id="pendingInvitationList">
                        <div class="text-muted">Checking notifications...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modals')
@include('layouts.modals.logout')
@endsection

@section('scripts')
<script>
(function () {
    const invitationList = document.getElementById('pendingInvitationList');
    const refreshButton = document.getElementById('refreshInvitationsBtn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function renderInvitations(notifications) {
        if (!invitationList) {
            return;
        }

        const invitations = (notifications || []).filter(function (notification) {
            return notification.type === 'workspace_invitation' && notification.read === false;
        });

        if (!invitations.length) {
            invitationList.innerHTML = `
                <div class="border rounded p-4 text-center bg-light">
                    <div class="mb-3">
                        <i class="bi bi-bell-slash text-muted" style="font-size: 2rem;"></i>
                    </div>
                    <h5 class="mb-2">No notifications yet</h5>
                    <p class="text-muted mb-0">Once a lawyer invites you to a workspace, the invitation will appear here.</p>
                </div>
            `;
            return;
        }

        invitationList.innerHTML = invitations.map(function (notification) {
            return `
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                        <div>
                            <h5 class="mb-1">${notification.title || 'Workspace Invitation'}</h5>
                            <p class="text-muted mb-1">${notification.message || ''}</p>
                            <small class="text-muted">${notification.created_at || ''}</small>
                        </div>
                        <button
                            type="button"
                            class="btn btn-info text-white btn-sm mt-2 mt-sm-0"
                            data-accept-invitation="${notification.id}"
                        >
                            Accept Invitation
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    function loadInvitations() {
        if (!invitationList) {
            return Promise.resolve();
        }

        invitationList.innerHTML = '<div class="text-muted">Checking notifications...</div>';

        return fetch("{{ route('notifications.index') }}", {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to load invitations.');
                }

                return response.json();
            })
            .then(function (payload) {
                renderInvitations(payload.notifications || []);
            })
            .catch(function (error) {
                invitationList.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        ${error.message || 'Unable to load invitations right now.'}
                    </div>
                `;
            });
    }

    invitationList?.addEventListener('click', function (event) {
        const button = event.target.closest('[data-accept-invitation]');

        if (!button) {
            return;
        }

        const invitationId = button.getAttribute('data-accept-invitation');

        if (!invitationId) {
            return;
        }

        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>Accepting...';

        fetch("{{ url('/notifications') }}/" + encodeURIComponent(invitationId) + "/accept", {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({})
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) {
                        throw new Error(payload.message || 'Invitation could not be accepted.');
                    }

                    return payload;
                });
            })
            .then(function () {
                window.location.href = "{{ route('dashboard') }}";
            })
            .catch(function (error) {
                button.disabled = false;
                button.textContent = 'Accept Invitation';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invitation Error',
                        text: error.message || 'Invitation could not be accepted.',
                        confirmButtonColor: '#0891b2'
                    });
                }
            });
    });

    refreshButton?.addEventListener('click', loadInvitations);
    loadInvitations();
})();
</script>
@endsection
