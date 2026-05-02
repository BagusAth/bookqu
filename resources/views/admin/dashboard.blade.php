@extends('layouts.admin')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        <x-admin.dashboard.header
            :user-name="$userName"
            :user-subtitle="$userSubtitle"
            :avatar-url="$avatarUrl"
        />

        <x-admin.dashboard.trial-banner :days-remaining="$trialDaysRemaining" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($summaryCards as $card)
                <x-admin.dashboard.stat-card
                    :title="$card['title']"
                    :value="$card['value']"
                    :trend="$card['trend']"
                    :icon="$card['icon']"
                    :icon-bg="$card['icon_bg']"
                    :icon-color="$card['icon_color']"
                    :trend-variant="$card['trend_variant']"
                />
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-admin.dashboard.revenue-chart :periods="$revenuePeriods" :series="$revenueSeries" active-period="Monthly" />
            <x-admin.dashboard.daily-trends :items="$dailyTrends" />
        </div>

        <x-admin.dashboard.recent-activity :items="$recentActivities" />
    </div>
@endsection
