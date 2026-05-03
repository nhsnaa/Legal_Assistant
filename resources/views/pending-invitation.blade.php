@extends('layouts.app')

@section('title', 'Pending Invitation')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
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
        </div>
    </div>
</div>
@endsection

@section('modals')
@include('layouts.modals.logout')
@endsection