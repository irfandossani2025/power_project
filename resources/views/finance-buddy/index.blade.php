<x-app-layout>
<div class="page-heading buddy-heading"><div><p>AI ASSISTANT</p><h1>Finance Buddy</h1><span>Ask about your business, prepare safe drafts, and review actions before anything is saved.</span></div><div class="buddy-state {{ $setting->enabled && $setting->api_key ? 'is-live' : '' }}"><i></i>{{ $setting->enabled && $setting->api_key ? 'AI provider connected' : 'Guided mode - no external data sharing' }}</div></div>

<section class="buddy-layout">
    <div class="buddy-chat panel">
        <div class="buddy-welcome"><div class="buddy-orb">✦</div><div><h2>Hello, {{ explode(' ', auth()->user()->name)[0] }}</h2><p>I know the modules you can access. I can explain records, flag items needing attention, and take you to a safe draft workflow. I never post, delete, send, or adjust records without your review.</p></div></div>
        @if(session('buddy_response'))
            @php($reply = session('buddy_response'))
            <div class="buddy-message user"><span>You asked</span><p>{{ $reply['prompt'] }}</p></div>
            <div class="buddy-message assistant"><span>Finance Buddy · {{ ucfirst($reply['provider']) }}</span><p>{{ $reply['answer'] }}</p>@if(!empty($reply['notice']))<small>{{ $reply['notice'] }}</small>@endif
                @if(!empty($reply['actions']))<div class="buddy-actions">@foreach($reply['actions'] as $action)<a class="{{ $action['type'] === 'draft' ? 'primary' : 'secondary' }}" href="{{ $action['url'] }}">{{ $action['label'] }}@if($action['type'] === 'draft') <em>Draft only</em>@endif</a>@endforeach</div>@endif
                @if($reply['status'] === 'needs_review')<div class="buddy-review">This recommendation opens a draft only. Review the document and save it yourself when it is correct.</div>@endif
            </div>
        @else
            <div class="buddy-message assistant"><span>Finance Buddy</span><p>Try: “What is overdue?”, “Which stock is low?”, “Create a quotation draft”, “Show project performance”, or “How much did we spend this month?”</p></div>
        @endif
        <form method="POST" action="{{ route('finance-buddy.ask') }}" class="buddy-composer">@csrf<textarea name="prompt" required maxlength="2000" placeholder="Ask Finance Buddy anything about your permitted workspace…">{{ old('prompt') }}</textarea><button class="primary">Ask Buddy <span>↗</span></button></form>
    </div>
    <aside class="buddy-sidebar">
        <section class="panel buddy-panel"><div class="panel-title"><div><h2>Workspace pulse</h2><p>Live, role-aware summary</p></div></div><div class="buddy-stats"><p><span>Open quotations</span><b>{{ $context['open_quotations'] }}</b></p><p><span>Low-stock items</span><b>{{ $context['low_stock_items'] }}</b></p><p><span>Active projects</span><b>{{ $context['active_projects'] }}</b></p>@if(isset($context['receivables_omr']))<p><span>Receivables</span><b>OMR {{ number_format($context['receivables_omr'], 3) }}</b></p>@endif</div></section>
        <section class="panel buddy-panel"><div class="panel-title"><div><h2>Safe shortcuts</h2><p>Everything stays under your control</p></div></div><div class="buddy-shortcuts"><a href="{{ route('quotations.create') }}">Create quotation draft <b>→</b></a><a href="{{ route('expenses.create') }}">Record expense draft <b>→</b></a><a href="{{ route('reports.index') }}">Open reports <b>→</b></a><a href="{{ route('imports.index') }}">Review QuickBooks import <b>→</b></a></div></section>
    </aside>
</section>

@if($activities->isNotEmpty())<section class="panel buddy-history"><div class="panel-title"><div><h2>Your recent Buddy activity</h2><p>Every request is logged for traceability</p></div></div>@foreach($activities as $activity)<div><span>{{ $activity->created_at->format('d M, H:i') }}</span><p>{{ $activity->prompt }}</p><b class="status {{ $activity->status === 'needs_review' ? 'sent' : 'accepted' }}">{{ $activity->status === 'needs_review' ? 'Review required' : 'Completed' }}</b></div>@endforeach</section>@endif
</x-app-layout>
