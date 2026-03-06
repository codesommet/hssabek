{{-- Signature block — uses the default active Signature from tenant settings --}}
@if(isset($signature) && $signature)
    @php
        $sigPath = $signature->getFirstMediaPath('signature');
    @endphp
    <div style="margin-top: 30px; text-align: right;">
        <div style="display: inline-block; text-align: center;">
            @if($sigPath && file_exists($sigPath))
                <img src="{{ $sigPath }}" height="60" alt="Signature" style="margin-bottom: 5px;">
            @endif
            <div style="font-size: 10px; color: #555; border-top: 1px solid #ccc; padding-top: 4px; min-width: 150px;">
                {{ $signature->name }}
            </div>
        </div>
    </div>
@endif
