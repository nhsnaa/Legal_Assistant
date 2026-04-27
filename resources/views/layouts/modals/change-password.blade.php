<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title font-weight-normal text-dark" style="font-size: 1.5rem;">Change Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body px-4 pb-4">
                <form action="{{ route('password.update') }}" method="POST">
                    @csrf
                    <div class="form-group mb-4">
                        <label class="small text-muted mb-1">Email</label>
                        <input type="email" class="form-control bg-light border" value="{{ session('email', 'No Email') }}" readonly style="border-radius: 8px; padding: 12px;">
                    </div>

                    <div class="form-group mb-4 position-relative">
                        <input type="password" class="form-control border @error('new_password') is-invalid @enderror" name="new_password" id="new_password" placeholder="New Password" style="border-radius: 8px; padding: 12px; height: 50px; padding-right: 45px;" required>

                        <span class="position-absolute d-flex align-items-center justify-content-center"
                            style="right: 15px; top: 0; bottom: 0; cursor: pointer; color: #aaa; width: 30px;"
                            onclick="toggleVisibility('new_password', 'icon_new')">
                            <i id="icon_new" class="bi bi-eye-slash" style="font-size: 1.2rem;"></i>
                        </span>
                        @error('new_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-4 position-relative">
                        <input type="password" class="form-control border @error('new_password_confirmation') is-invalid @enderror" name="new_password_confirmation" id="confirm_password" placeholder="Confirm Password" style="border-radius: 8px; padding: 12px; height: 50px; padding-right: 45px;" required>

                        <span class="position-absolute d-flex align-items-center justify-content-center"
                            style="right: 15px; top: 0; bottom: 0; cursor: pointer; color: #aaa; width: 30px;"
                            onclick="toggleVisibility('confirm_password', 'icon_confirm')">
                            <i id="icon_confirm" class="bi bi-eye-slash" style="font-size: 1.2rem;"></i>
                        </span>
                        @error('new_password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-start">
                        <button type="button" class="btn btn-outline-secondary mr-3 px-4" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="submit" class="btn btn-light px-4 border" style="border-radius: 8px; background-color: #e9ecef; color: #495057;">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
