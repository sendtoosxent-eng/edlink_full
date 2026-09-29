@php($branchChoices = auth()->user()->schoolAccesses()->orderBy('name')->get())
@if($branchChoices->count() > 1)
    <div class='hidden sm:flex items-center gap-2'>
        @if(auth()->user()->canViewGroupDashboard())
            <a href='{{ route('group-dashboard') }}' class='rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:border-yellow-400'>All branches</a>
        @endif
        <form method='POST' action='{{ route('branch-context.update') }}'>@csrf @method('PUT')
            <select name='school_id' onchange='this.form.submit()' aria-label='Active branch' class='max-w-52 rounded-xl border-slate-200 py-2 pl-3 pr-8 text-xs font-bold text-slate-700'>
                @foreach($branchChoices as $branch)<option value='{{ $branch->id }}' @selected($branch->id === auth()->user()->school_id)>{{ $branch->branch_name ?: $branch->name }}</option>@endforeach
            </select>
        </form>
    </div>
@endif
