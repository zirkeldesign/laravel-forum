<div id="post-{{ $post->sequence }}"
    class="post card mb-2 {{ $post->trashed() ? 'deleted' : '' }}"
    :class="{ 'border-primary': selectedPosts.includes({{ $post->id }}) }">
    <div class="card-header">
        @if (! isset($single) || ! $single)
            <span class="float-right">
                <a href="{{ Forum::route('thread.show', $post) }}">#{{ $post->sequence }}</a>
                @can ('deletePosts', $post->thread)
                    <input type="checkbox" :value="{{ $post->id }}" v-model="selectedPosts">
                @endcan
            </span>
        @endif

        {{ $post->authorName }}

        <span class="text-muted">
            {{ $post->posted }}
            @if ($post->hasBeenUpdated())
                ({{ trans('forum::general.last_updated') }} {{ $post->updated }})
            @endif
        </span>
    </div>
    <div class="card-body">
        @if (! is_null($post->parent))
            @include ('forum::post.partials.quote', ['post' => $post->parent])
        @endif

        @if ($post->trashed())
            <span class="badge badge-pill badge-danger">{{ trans('forum::general.deleted') }}</span>
            @can ('viewTrashedPosts')
                <br>
                {!! Forum::render($post->content) !!}
            @endcan
        @else
            {!! Forum::render($post->content) !!}
        @endif

        <div class="text-right">
            @if (Request::fullUrl() != Forum::route('post.show', $post) && ! $post->trashed())
                    <a href="{{ Forum::route('post.show', $post) }}" class="card-link text-muted">{{ trans('forum::general.permalink') }}</a>
            @endif
            @if (! $post->trashed())
                @can('edit', $post)
                    <a href="{{ Forum::route('post.edit', $post) }}" class="card-link">{{ trans('forum::general.edit') }}</a>
                @endcan
            @endif
            @if (! $post->trashed() && (! isset($single) || ! $single))
                @can('reply', $post->thread)
                    <a href="{{ Forum::route('post.create', $post) }}" class="card-link">{{ trans('forum::general.reply') }}</a>
                @endcan
            @endif
        </div>
    </div>
</div>