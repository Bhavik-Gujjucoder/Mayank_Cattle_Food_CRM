<h3>{{ $page_title }}</h3>

<div class="card des-deler-form">
    <div class="card-body view-page">
        <div class="edit-distributorsform">

            {{-- ================= BASIC INFORMATION ================= --}}
            <div class="applicationdtl delerbox-border-b">
                {{-- <div class="section-title">Basic Information</div> --}}
                <div class="distributer-cls">

                    {{-- Profile Image --}}
                    @if (!empty($dealer->user->profile_picture))
                        <div class="col-md-12">
                            <div class="profile-pic-upload">
                                <div class="profile-pic">
                                    <img src="{{ asset('storage/profile_pictures/' . $dealer->user->profile_picture) }}"
                                        class="img-thumbnail mb-2">
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Broker --}}
                    @if (!empty($dealer->broker))
                        <div class="info-row">
                            <label class="col-form-label">Broker Person :</label>
                            <span class="info-value">{{ $dealer->broker->name ?? '-' }}</span>
                        </div>
                    @endif

                    {{-- Code No --}}
                    @if (!empty($dealer->code_no))
                        <div class="info-row">
                            <label class="col-form-label">Code No :</label>
                            <span class="info-value">{{ $dealer->code_no }}</span>
                        </div>
                    @endif

                    {{-- Applicant Name --}}
                    @if (!empty($dealer->user->name))
                        <div class="info-row">
                            <label class="col-form-label">Applicant Name :</label>
                            <span class="info-value">{{ $dealer->user->name }}</span>
                        </div>
                    @endif

                    {{-- Firm Name --}}
                    @if (!empty($dealer->firm_shop_name))
                        <div class="info-row">
                            <label class="col-form-label">Firm / Shop Name :</label>
                            <span class="info-value">{{ $dealer->firm_shop_name }}</span>
                        </div>
                    @endif

                    {{-- Address --}}
                    @if (!empty($dealer->firm_shop_address))
                        <div class="info-row">
                            <label class="col-form-label">Firm / Shop Address :</label>
                            <span class="info-value">{{ $dealer->firm_shop_address }}</span>
                        </div>
                    @endif

                    {{-- Mobile --}}
                    @if (!empty($dealer->user->phone_no))
                        <div class="info-row">
                            <label class="col-form-label">Mobile No :</label>
                            <span class="info-value">{{ $dealer->user->phone_no }}</span>
                        </div>
                    @endif

                    {{-- Email --}}
                    @if (!empty($dealer->user->email))
                        <div class="info-row">
                            <label class="col-form-label">Email :</label>
                            <span class="info-value">{{ $dealer->user->email }}</span>
                        </div>
                    @endif

                    {{-- PAN --}}
                    @if (!empty($dealer->pancard))
                        <div class="info-row">
                            <label class="col-form-label">PAN Card No :</label>
                            <span class="info-value">{{ $dealer->pancard }}</span>
                        </div>
                    @endif

                    {{-- GST --}}
                    @if (!empty($dealer->gstin))
                        <div class="info-row">
                            <label class="col-form-label">GSTIN :</label>
                            <span class="info-value">{{ $dealer->gstin }}</span>
                        </div>
                    @endif

                    {{-- Aadhar --}}
                    @if (!empty($dealer->aadhar_card))
                        <div class="info-row">
                            <label class="col-form-label">Aadhar Card No :</label>
                            <span class="info-value">{{ $dealer->aadhar_card }}</span>
                        </div>
                    @endif

                    {{-- State --}}
                    @if (!empty($dealer->state))
                        <div class="info-row">
                            <label class="col-form-label">State :</label>
                            <span class="info-value">{{ $dealer->state->state_name ?? '-' }}</span>
                        </div>
                    @endif

                    {{-- City --}}
                    @if (!empty($dealer->city))
                        <div class="info-row">
                            <label class="col-form-label">City :</label>
                            <span class="info-value">{{ $dealer->city->city_name ?? '-' }}</span>
                        </div>
                    @endif

                    {{-- Postal Code --}}
                    @if (!empty($dealer->postal_code))
                        <div class="info-row">
                            <label class="col-form-label">Postal Code :</label>
                            <span class="info-value">{{ $dealer->postal_code }}</span>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
