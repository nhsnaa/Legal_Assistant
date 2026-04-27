<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px; border: none; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
            <div class="modal-body text-center p-5">
                <div style="font-size: 3rem; margin-bottom: 15px;"><i class="bi bi-door-open-fill"></i></div>
                <h4 class="font-weight-bold">Confirm Logout</h4>
                <p class="text-muted">Are you sure you want to leave, {{ session('first_name') ?: session('user_name', 'User') }}?</p>
                <div class="d-flex justify-content-center mt-4">
                    <button type="button" class="btn btn-light px-4 mr-2" data-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger px-4">Yes, Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
