@php
    $user         = $dealer->user;
    $name         = $user?->name ?? '-';
    $nameParts    = array_filter(explode(' ', $name));
    $initials     = collect($nameParts)->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->implode('');
    $hasPic       = !empty($user?->profile_picture);
    $picUrl       = $hasPic ? asset('storage/profile_pictures/' . $user->profile_picture) : null;
    $hasDocuments = !empty($dealer->pancard) || !empty($dealer->gstin) || !empty($dealer->aadhar_card);
    $hasLocation  = !empty($dealer->state) || !empty($dealer->city) || !empty($dealer->postal_code);
@endphp

<div class="ddp-wrap">

    {{-- ══════════════════ HEADER ══════════════════ --}}
    <div class="ddp-header">

        {{-- Avatar (photo or initials) --}}
        <div class="ddp-avatar-wrap">
            @if ($hasPic)
                <img src="{{ $picUrl }}" alt="{{ e($name) }}" class="ddp-avatar-img">
            @else
                <div class="ddp-avatar-initials">{{ $initials ?: '?' }}</div>
            @endif
        </div>

        {{-- Name + chips --}}
        <div class="ddp-header-info">
            <div class="ddp-dealer-name">{{ $name }}</div>
            <div class="ddp-header-chips">
                @if (!empty($dealer->code_no))
                    <span class="ddp-chip ddp-chip-code">
                        <i class="ti ti-hash"></i> {{ $dealer->code_no }}
                    </span>
                @endif
                @if (!empty($dealer->brand?->name))
                    <span class="ddp-chip ddp-chip-brand">
                        <i class="ti ti-rosette-discount"></i> {{ $dealer->brand->name }}
                    </span>
                @endif
                <span class="ddp-chip ddp-chip-role">
                    <i class="ti ti-user-check"></i> Dealer
                </span>
            </div>
        </div>

    </div>{{-- /.ddp-header --}}


    {{-- ══════════════════ BODY ══════════════════ --}}
    <div class="ddp-body">

        {{-- ── Business Info ─────────────────────────── --}}
        <div class="ddp-section">
            <div class="ddp-section-head">
                <i class="ti ti-building-store"></i> Business Info
            </div>
            <div class="ddp-fields-grid">

                @if (!empty($dealer->firm_shop_name))
                    <div class="ddp-field">
                        <span class="ddp-field-icon ddp-icon-blue"><i class="ti ti-building-store"></i></span>
                        <div>
                            <div class="ddp-field-label">Firm / Shop Name</div>
                            <div class="ddp-field-value">{{ $dealer->firm_shop_name }}</div>
                        </div>
                    </div>
                @endif

                @if (!empty($dealer->broker?->name))
                    <div class="ddp-field">
                        <span class="ddp-field-icon ddp-icon-indigo"><i class="ti ti-user-circle"></i></span>
                        <div>
                            <div class="ddp-field-label">Broker Person</div>
                            <div class="ddp-field-value">{{ $dealer->broker->name }}</div>
                        </div>
                    </div>
                @endif

                @if (!empty($dealer->firm_shop_address))
                    <div class="ddp-field ddp-field-full">
                        <span class="ddp-field-icon ddp-icon-blue"><i class="ti ti-map-2"></i></span>
                        <div>
                            <div class="ddp-field-label">Firm / Shop Address</div>
                            <div class="ddp-field-value">{{ $dealer->firm_shop_address }}</div>
                        </div>
                    </div>
                @endif

            </div>
        </div>


        {{-- ── Contact ────────────────────────────────── --}}
        @if (!empty($dealer->user?->phone_no) || !empty($dealer->user?->email))
            <div class="ddp-section">
                <div class="ddp-section-head">
                    <i class="ti ti-address-book"></i> Contact
                </div>
                <div class="ddp-fields-grid">

                    @if (!empty($dealer->user?->phone_no))
                        <div class="ddp-field">
                            <span class="ddp-field-icon ddp-icon-green"><i class="ti ti-phone"></i></span>
                            <div>
                                <div class="ddp-field-label">Mobile No</div>
                                <div class="ddp-field-value">{{ $dealer->user->phone_no }}</div>
                            </div>
                        </div>
                    @endif

                    @if (!empty($dealer->user?->email))
                        <div class="ddp-field">
                            <span class="ddp-field-icon ddp-icon-blue"><i class="ti ti-mail"></i></span>
                            <div>
                                <div class="ddp-field-label">Email Address</div>
                                <div class="ddp-field-value">{{ $dealer->user->email }}</div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        @endif


        {{-- ── Location ───────────────────────────────── --}}
        @if ($hasLocation)
            <div class="ddp-section">
                <div class="ddp-section-head">
                    <i class="ti ti-map-pin"></i> Location
                </div>
                <div class="ddp-fields-grid">

                    @if (!empty($dealer->state?->state_name))
                        <div class="ddp-field">
                            <span class="ddp-field-icon ddp-icon-purple"><i class="ti ti-map"></i></span>
                            <div>
                                <div class="ddp-field-label">State</div>
                                <div class="ddp-field-value">{{ $dealer->state->state_name }}</div>
                            </div>
                        </div>
                    @endif

                    @if (!empty($dealer->city?->city_name))
                        <div class="ddp-field">
                            <span class="ddp-field-icon ddp-icon-purple"><i class="ti ti-building-community"></i></span>
                            <div>
                                <div class="ddp-field-label">City</div>
                                <div class="ddp-field-value">{{ $dealer->city->city_name }}</div>
                            </div>
                        </div>
                    @endif

                    @if (!empty($dealer->postal_code))
                        <div class="ddp-field">
                            <span class="ddp-field-icon ddp-icon-purple"><i class="ti ti-mailbox"></i></span>
                            <div>
                                <div class="ddp-field-label">Postal Code</div>
                                <div class="ddp-field-value">{{ $dealer->postal_code }}</div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        @endif


        {{-- ── Documents ──────────────────────────────── --}}
        @if ($hasDocuments)
            <div class="ddp-section ddp-section-last">
                <div class="ddp-section-head">
                    <i class="ti ti-id-badge-2"></i> Identity Documents
                </div>
                <div class="ddp-fields-grid">

                    @if (!empty($dealer->pancard))
                        <div class="ddp-field">
                            <span class="ddp-field-icon ddp-icon-amber"><i class="ti ti-credit-card"></i></span>
                            <div>
                                <div class="ddp-field-label">PAN Card No</div>
                                <div class="ddp-field-value ddp-mono">{{ $dealer->pancard }}</div>
                            </div>
                        </div>
                    @endif

                    @if (!empty($dealer->gstin))
                        <div class="ddp-field">
                            <span class="ddp-field-icon ddp-icon-amber"><i class="ti ti-receipt-tax"></i></span>
                            <div>
                                <div class="ddp-field-label">GSTIN</div>
                                <div class="ddp-field-value ddp-mono">{{ $dealer->gstin }}</div>
                            </div>
                        </div>
                    @endif

                    @if (!empty($dealer->aadhar_card))
                        <div class="ddp-field">
                            <span class="ddp-field-icon ddp-icon-amber"><i class="ti ti-id-badge"></i></span>
                            <div>
                                <div class="ddp-field-label">Aadhar Card No</div>
                                <div class="ddp-field-value ddp-mono">{{ $dealer->aadhar_card }}</div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        @endif

    </div>{{-- /.ddp-body --}}

</div>{{-- /.ddp-wrap --}}
