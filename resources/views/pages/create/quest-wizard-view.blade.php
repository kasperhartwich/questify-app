<div class="flex flex-col">
    {{-- Header --}}
    <div class="flex items-center justify-between px-4 pb-3.5 pt-1.5">
        <h1 class="font-heading text-xl font-extrabold text-bark">{{ __('general.create_quest') ?? 'Create Quest' }}</h1>
        <button wire:click="saveDraft" class="text-[11px] font-semibold text-forest-400">{{ __('general.save_draft') ?? 'Save Draft' }}</button>
    </div>

    {{-- Step Indicator --}}
    <div class="flex items-center gap-1.5 px-4 pb-3">
        @foreach (['Basics', 'Checkpoints', 'Questions', 'Rules', 'Review'] as $i => $label)
            <button
                wire:click="goToStep({{ $i + 1 }})"
                class="flex flex-1 flex-col items-center gap-1"
            >
                <div class="h-[3px] w-full rounded-full {{ $step > $i ? 'bg-forest-600' : 'bg-cream-border' }}"></div>
                <span class="text-[8px] font-semibold {{ $step === $i + 1 ? 'text-forest-600' : 'text-muted' }}">{{ $label }}</span>
            </button>
        @endforeach
    </div>

    <div class="flex-1 overflow-y-auto px-3.5 pb-4">
        {{-- Step 1: Basics --}}
        @if ($step === 1)
            <div class="flex flex-col gap-2.5">
                {{-- Quest Name --}}
                <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white p-3.5">
                    <label class="mb-1.5 block text-[9px] font-bold uppercase tracking-widest text-muted">{{ __('quests.title') }}</label>
                    <input type="text" wire:model="title" class="w-full border-none bg-transparent p-0 font-heading text-sm font-bold text-bark placeholder-muted/50 focus:outline-none focus:ring-0" placeholder="{{ __('quests.title') }}" />
                    @error('title') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                </div>

                {{-- Description --}}
                <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white p-3.5">
                    <label class="mb-1.5 block text-[9px] font-bold uppercase tracking-widest text-muted">{{ __('quests.description') }}</label>
                    <textarea wire:model="description" rows="3" class="w-full border-none bg-transparent p-0 text-[13px] text-bark placeholder-muted/50 focus:outline-none focus:ring-0" placeholder="{{ __('quests.description') }}"></textarea>
                </div>

                {{-- Category & Difficulty --}}
                <div class="flex gap-2">
                    <div class="flex-1 rounded-[14px] border-[1.5px] border-cream-border bg-white p-3.5">
                        <label class="mb-1.5 block text-[9px] font-bold uppercase tracking-widest text-muted">{{ __('general.category') }}</label>
                        <select wire:model="categoryId" class="w-full border-none bg-transparent p-0 font-heading text-xs font-bold text-bark focus:outline-none focus:ring-0">
                            <option value="">{{ __('general.all_categories') }}</option>
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('categoryId') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex-1 rounded-[14px] border-[1.5px] border-cream-border bg-white p-3.5">
                        <label class="mb-1.5 block text-[9px] font-bold uppercase tracking-widest text-muted">{{ __('general.difficulty') }}</label>
                        <select wire:model="difficulty" class="w-full border-none bg-transparent p-0 font-heading text-xs font-bold text-bark focus:outline-none focus:ring-0">
                            <option value="">{{ __('general.all_difficulties') }}</option>
                            @foreach (\App\Enums\Difficulty::cases() as $diff)
                                <option value="{{ $diff->value }}">{{ ucfirst($diff->value) }}</option>
                            @endforeach
                        </select>
                        @error('difficulty') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Cover Image --}}
                <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white p-3.5">
                    <label class="mb-1.5 block text-[9px] font-bold uppercase tracking-widest text-muted">{{ __('general.cover_image') ?? 'Cover Image' }}</label>
                    <input type="file" wire:model="coverImage" accept="image/*" class="w-full text-xs text-muted" />
                    @error('coverImage') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        {{-- Step 2: Checkpoints --}}
        @if ($step === 2)
            <div class="flex flex-col gap-2.5">
                <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white p-3.5">
                    <div class="mb-2.5 text-[9px] font-bold uppercase tracking-widest text-muted">
                        {{ __('quests.checkpoints') }}
                        <span class="font-normal normal-case text-forest-400">{{ count($checkpoints) }} {{ __('general.added') ?? 'added' }}</span>
                    </div>

                @foreach ($checkpoints as $cpIndex => $checkpoint)
                    <div class="flex items-center gap-2 border-b border-cream-border py-1.5 last:border-b-0" wire:key="cp-{{ $cpIndex }}">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-forest-600 text-[9px] font-bold text-white">{{ $cpIndex + 1 }}</div>
                        <div class="min-w-0 flex-1">
                            <input type="text" wire:model="checkpoints.{{ $cpIndex }}.title" class="w-full border-none bg-transparent p-0 text-[11px] font-semibold text-bark placeholder-muted/50 focus:outline-none focus:ring-0" placeholder="{{ __('quests.title') }}" />
                            @error("checkpoints.{$cpIndex}.title") <p class="text-[10px] text-coral">{{ $message }}</p> @enderror
                        </div>
                        @if (count($checkpoints) > 1)
                            <button wire:click="removeCheckpoint({{ $cpIndex }})" class="shrink-0">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            </button>
                        @else
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
                        @endif
                    </div>

                        <div class="mt-2 space-y-3 border-t border-cream-border pt-2" x-data="{ expanded: false }" x-show="expanded" x-transition>
                            <div>
                                <textarea wire:model="checkpoints.{{ $cpIndex }}.description" rows="2" class="w-full rounded-xl border-[1.5px] border-cream-border bg-white px-3 py-2 text-xs text-bark focus:border-forest-600 focus:outline-none" placeholder="{{ __('quests.description') }}"></textarea>
                            </div>

                            {{-- Map Pin Drop --}}
                            <div>
                                <div
                                    class="h-48 w-full overflow-hidden rounded-xl bg-cream-dark"
                                    x-data="{
                                        map: null,
                                        marker: null,
                                        init() {
                                            if (typeof google === 'undefined') return;
                                            const lat = {{ $checkpoint['latitude'] ?? 55.6761 }};
                                            const lng = {{ $checkpoint['longitude'] ?? 12.5683 }};
                                            this.map = new google.maps.Map(this.$el, {
                                                center: { lat, lng },
                                                zoom: 13,
                                                mapTypeControl: false,
                                                streetViewControl: false,
                                            });
                                            @if ($checkpoint['latitude'] && $checkpoint['longitude'])
                                                this.marker = new google.maps.Marker({
                                                    position: { lat, lng },
                                                    map: this.map,
                                                    draggable: true,
                                                });
                                                this.marker.addListener('dragend', (e) => {
                                                    $wire.updateCheckpointCoordinates({{ $cpIndex }}, e.latLng.lat(), e.latLng.lng());
                                                });
                                            @endif
                                            this.map.addListener('click', (e) => {
                                                if (this.marker) this.marker.setMap(null);
                                                this.marker = new google.maps.Marker({
                                                    position: e.latLng,
                                                    map: this.map,
                                                    draggable: true,
                                                });
                                                this.marker.addListener('dragend', (ev) => {
                                                    $wire.updateCheckpointCoordinates({{ $cpIndex }}, ev.latLng.lat(), ev.latLng.lng());
                                                });
                                                $wire.updateCheckpointCoordinates({{ $cpIndex }}, e.latLng.lat(), e.latLng.lng());
                                            });
                                        }
                                    }"
                                >
                                    <div class="flex h-full items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('general.loading') }}
                                    </div>
                                </div>
                                @if ($checkpoint['latitude'] && $checkpoint['longitude'])
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">📍 {{ number_format($checkpoint['latitude'], 5) }}, {{ number_format($checkpoint['longitude'], 5) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                    {{-- Add checkpoint --}}
                    <button wire:click="addCheckpoint" class="flex items-center gap-2 pt-2.5">
                        <div class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-dashed border-cream-border">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        </div>
                        <span class="text-[11px] font-semibold text-forest-400">{{ __('general.add_checkpoint_on_map') ?? 'Add checkpoint on map' }}</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- Step 3: Questions --}}
        @if ($step === 3)
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('quests.questions') }}</h2>

                @foreach ($checkpoints as $cpIndex => $checkpoint)
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700" wire:key="q-cp-{{ $cpIndex }}">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $checkpoint['title'] ?: __('quests.checkpoint') . ' ' . ($cpIndex + 1) }}</h3>
                            <button wire:click="addQuestion({{ $cpIndex }})" class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-medium text-white">+ {{ __('quests.question') }}</button>
                        </div>

                        @foreach ($questions[$cpIndex] ?? [] as $qIndex => $question)
                            <div class="mb-4 rounded-lg border border-gray-200 p-3 dark:border-gray-600" wire:key="q-{{ $cpIndex }}-{{ $qIndex }}">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quests.question') }} {{ $qIndex + 1 }}</span>
                                    <button wire:click="removeQuestion({{ $cpIndex }}, {{ $qIndex }})" class="text-xs text-red-500">{{ __('general.delete') }}</button>
                                </div>

                                <div class="space-y-2">
                                    <input type="text" wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.body" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('quests.question') }}..." />
                                    @error("questions.{$cpIndex}.{$qIndex}.body") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror

                                    <div class="grid grid-cols-2 gap-2">
                                        <select wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.type" wire:change="onQuestionTypeChanged({{ $cpIndex }}, {{ $qIndex }})" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            @foreach (\App\Enums\QuestionType::cases() as $type)
                                                <option value="{{ $type->value }}">{{ str_replace('_', ' ', ucfirst($type->value)) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.points" min="1" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('quests.points') }}" />
                                    </div>

                                    <input type="text" wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.hint" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Hint (optional)" />

                                    {{-- Answers (not for OpenEnded) --}}
                                    @if ($question['type'] !== \App\Enums\QuestionType::OpenText->value)
                                        <div class="mt-2 space-y-2">
                                            <label class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('quests.answers') }}</label>
                                            @foreach ($question['answers'] as $aIndex => $answer)
                                                <div class="flex items-center gap-2" wire:key="a-{{ $cpIndex }}-{{ $qIndex }}-{{ $aIndex }}">
                                                    <input
                                                        type="{{ $question['type'] === \App\Enums\QuestionType::TrueFalse->value ? 'radio' : 'checkbox' }}"
                                                        wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.answers.{{ $aIndex }}.is_correct"
                                                        {{ $question['type'] === \App\Enums\QuestionType::TrueFalse->value ? 'name=correct_' . $cpIndex . '_' . $qIndex : '' }}
                                                        class="h-4 w-4 text-forest-600"
                                                    />
                                                    <input type="text" wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.answers.{{ $aIndex }}.body" class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('quests.answer') }} {{ $aIndex + 1 }}" {{ $question['type'] === \App\Enums\QuestionType::TrueFalse->value ? 'disabled' : '' }} />
                                                    @if ($question['type'] !== \App\Enums\QuestionType::TrueFalse->value && count($question['answers']) > 2)
                                                        <button wire:click="removeAnswer({{ $cpIndex }}, {{ $qIndex }}, {{ $aIndex }})" class="text-xs text-red-500">✕</button>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if ($question['type'] === \App\Enums\QuestionType::MultipleChoice->value)
                                                <button wire:click="addAnswer({{ $cpIndex }}, {{ $qIndex }})" class="text-xs text-forest-600 dark:text-forest-400">+ {{ __('quests.answer') }}</button>
                                            @endif
                                        </div>
                                    @else
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('sessions.open_ended_note') }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        @if (empty($questions[$cpIndex]))
                            <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('quests.no_questions') }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Step 4: Game Rules --}}
        @if ($step === 4)
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('sessions.game_rules') }}</h2>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('sessions.play_mode') }}</label>
                    <select wire:model="playMode" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach (\App\Enums\PlayMode::cases() as $mode)
                            <option value="{{ $mode->value }}">{{ ucfirst($mode->value) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('sessions.wrong_answer_behaviour') }}</label>
                    <select wire:model="wrongAnswerBehaviour" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach (\App\Enums\WrongAnswerBehaviour::cases() as $behaviour)
                            <option value="{{ $behaviour->value }}">{{ str_replace('_', ' ', ucfirst($behaviour->value)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quests.time_limit') }} ({{ __('sessions.seconds') }})</label>
                    <input type="number" wire:model="timeLimitPerQuestion" min="5" max="300" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="shuffleQuestions" id="shuffleQ" class="h-4 w-4 rounded text-forest-600" />
                    <label for="shuffleQ" class="text-sm text-gray-700 dark:text-gray-300">{{ __('sessions.shuffle_questions') }}</label>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="shuffleAnswers" id="shuffleA" class="h-4 w-4 rounded text-forest-600" />
                    <label for="shuffleA" class="text-sm text-gray-700 dark:text-gray-300">{{ __('sessions.shuffle_answers') }}</label>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('sessions.max_participants') }}</label>
                    <input type="number" wire:model="maxParticipants" min="2" max="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('sessions.unlimited') }}" />
                </div>
            </div>
        @endif

        {{-- Step 5: Review & Publish --}}
        @if ($step === 5)
            <div class="flex flex-col gap-2.5">
                {{-- Quest Name Card --}}
                <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white p-3.5">
                    <div class="text-[9px] font-bold uppercase tracking-widest text-muted">{{ __('quests.quest') }}</div>
                    <h3 class="mt-1 font-heading text-sm font-bold text-bark">{{ $title }}</h3>
                    @if ($description)
                        <p class="mt-1 text-[11px] text-muted">{{ Str::limit($description, 100) }}</p>
                    @endif
                </div>

                {{-- Checkpoints Card --}}
                <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white p-3.5">
                    <div class="mb-2 text-[9px] font-bold uppercase tracking-widest text-muted">
                        {{ __('quests.checkpoints') }}
                        <span class="font-normal normal-case text-forest-400">{{ count($checkpoints) }} {{ __('general.added') ?? 'added' }}</span>
                    </div>
                    @foreach ($checkpoints as $cpIndex => $checkpoint)
                        <div class="flex items-center gap-2 border-b border-cream-border py-1.5 last:border-b-0" wire:key="review-cp-{{ $cpIndex }}">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-forest-600 text-[9px] font-bold text-white">{{ $cpIndex + 1 }}</div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-semibold text-bark">{{ $checkpoint['title'] }}</p>
                                <p class="text-[9px] text-muted">{{ count($questions[$cpIndex] ?? []) }} {{ __('quests.question') ?? 'question' }}{{ count($questions[$cpIndex] ?? []) !== 1 ? 's' : '' }}</p>
                            </div>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
                        </div>
                    @endforeach
                </div>

                {{-- Settings Row --}}
                <div class="flex gap-2">
                    <div class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white p-2.5 text-center">
                        <div class="text-[9px] text-muted">{{ __('general.visibility') ?? 'Visibility' }}</div>
                        <div class="font-heading text-xs font-bold text-bark">{{ ucfirst($visibility ?? 'Public') }}</div>
                    </div>
                    <div class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white p-2.5 text-center">
                        <div class="text-[9px] text-muted">{{ __('general.mode') ?? 'Mode' }}</div>
                        <div class="font-heading text-xs font-bold text-bark">{{ ucfirst($playMode ?? 'Any') }}</div>
                    </div>
                    <div class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white p-2.5 text-center">
                        <div class="text-[9px] text-muted">{{ __('general.scoring') ?? 'Scoring' }}</div>
                        <div class="font-heading text-xs font-bold text-bark">{{ $scoringSpeedBonus ? 'Speed' : 'Standard' }}</div>
                    </div>
                </div>

                {{-- Publish --}}
                <button wire:click="publish" class="mt-1 flex w-full items-center justify-center gap-2 rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark">
                    {{ __('quests.publish') }} &rarr;
                </button>
            </div>
        @endif
    </div>

    {{-- Bottom Navigation (steps 1-4) --}}
    @if ($step < 5)
        <div class="border-t border-cream-border bg-white px-3.5 py-3">
            <div class="flex gap-3">
                @if ($step > 1)
                    <button wire:click="previousStep" class="flex-1 rounded-xl border-[1.5px] border-cream-border px-4 py-3 text-[13px] font-semibold text-bark">
                        {{ __('general.previous') }}
                    </button>
                @endif
                <button wire:click="nextStep" class="flex-1 rounded-xl bg-amber-400 px-4 py-3 font-heading text-sm font-bold text-bark">
                    {{ __('general.next') }}
                </button>
            </div>
        </div>
    @endif
</div>
