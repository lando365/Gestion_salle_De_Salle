<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Payment;
use Carbon\Carbon;

class MemberService
{
    public function createMember(array $data)
    {
        $member = Member::create($data);
        
        // Enregistrer le paiement initial si nécessaire
        if (isset($data['initial_payment'])) {
            Payment::create([
                'member_id' => $member->id,
                'amount' => $data['initial_payment']['amount'],
                'payment_method' => $data['initial_payment']['method'],
                'payment_date' => Carbon::today(),
            ]);
        }

        return $member;
    }

    public function updateMemberStatus(Member $member)
    {
        $status = $member->subscription_end < Carbon::today() ? 'expired' : 'active';
        $member->update(['status' => $status]);
        return $member;
    }
}