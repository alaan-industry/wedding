<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:subheading>{{ __('Everything you need to invite your guests and follow their replies.') }}</flux:subheading>
        </div>

        @can('manage-invitations')
            <div class="grid gap-4 md:grid-cols-2">
                <flux:card class="space-y-3">
                    <flux:heading size="lg">{{ __('Guest list') }}</flux:heading>
                    <flux:text>{{ __('Add households, copy their personal invitation message, and see who is coming.') }}</flux:text>
                    <flux:button variant="primary" icon="users" :href="route('guests.index')" wire:navigate>{{ __('Manage guests') }}</flux:button>
                </flux:card>

                <flux:card class="space-y-3">
                    <flux:heading size="lg">{{ __('General invitation link') }}</flux:heading>
                    <flux:text>{{ __('Share this link with everyone. Visitors can read the invitation and leave a wish, while RSVPs stay on personal links.') }}</flux:text>
                    <flux:input :value="route('home')" readonly copyable />
                </flux:card>
            </div>
        @else
            <flux:callout icon="lock-closed" variant="secondary">
                <flux:callout.heading>{{ __('Guest management is restricted') }}</flux:callout.heading>
                <flux:callout.text>{{ __('Only the couple can manage the guest list. Add your e-mail address to WEDDING_ADMIN_EMAILS to get access.') }}</flux:callout.text>
            </flux:callout>
        @endcan
    </div>
</x-layouts::app>
