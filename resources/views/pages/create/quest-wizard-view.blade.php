<div class="flex min-h-screen flex-col bg-cream">

    {{-- ============================================================ --}}
    {{-- STEP 1: Quest Info --}}
    {{-- ============================================================ --}}
    @if ($step === 1)
        <div class="flex flex-1 flex-col px-4">
            {{-- Header --}}
            <x-step-indicator :current="1" :total="4" back-url="/" />

            <h1 class="font-heading text-[22px] font-extrabold text-bark">{{ __('general.quest_info') }}</h1>
            <p class="mb-5 mt-1 text-[13px] text-muted">{{ __('general.quest_info_subtitle') }}</p>

            <div class="flex flex-1 flex-col gap-4 pb-4">
                {{-- Quest Name --}}
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.quest_name') }}</label>
                    <input
                        type="text"
                        wire:model="title"
                        class="w-full rounded-[12px] border-2 border-cream-border bg-white px-3.5 py-3 text-[14px] font-semibold text-bark placeholder-muted/50 focus:border-forest-600 focus:outline-none focus:ring-0"
                        placeholder="{{ __('quests.title') }}"
                    />
                    @error('title') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('quests.description') }}</label>
                    <textarea
                        wire:model="description"
                        rows="3"
                        class="min-h-[72px] w-full rounded-[12px] border-2 border-cream-border bg-white px-3.5 py-3 text-[13px] text-bark placeholder-muted/50 focus:border-forest-600 focus:outline-none focus:ring-0"
                        placeholder="{{ __('quests.description') }}"
                    ></textarea>
                </div>

                {{-- Category (chip selector) --}}
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.category') }}</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($categories as $id => $name)
                            <button
                                type="button"
                                wire:click="$set('categoryId', '{{ $id }}')"
                                class="rounded-full border-[1.5px] px-[14px] py-[7px] text-[13px] font-semibold transition-colors
                                    {{ $categoryId == $id
                                        ? 'border-forest-600 bg-forest-600 text-white'
                                        : 'border-cream-border bg-white text-muted' }}"
                            >
                                {{ $name }}
                            </button>
                        @endforeach
                    </div>
                    @error('categoryId') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                </div>

                {{-- Difficulty (segmented control) --}}
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.difficulty') }}</label>
                    <div class="flex rounded-[11px] bg-cream-dark p-[3px]">
                        @foreach (\App\Enums\Difficulty::cases() as $diff)
                            <button
                                type="button"
                                wire:click="$set('difficulty', '{{ $diff->value }}')"
                                class="flex-1 rounded-[9px] py-2 text-center text-[13px] font-semibold transition-all
                                    {{ $difficulty === $diff->value
                                        ? 'bg-white text-bark shadow-sm'
                                        : 'text-muted' }}"
                            >
                                {{ ucfirst($diff->value) }}
                            </button>
                        @endforeach
                    </div>
                    @error('difficulty') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                </div>

                {{-- Language --}}
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.language') }}</label>
                    <div class="flex items-center gap-2">
                        <span class="text-[15px]">{{ app()->getLocale() === 'da' ? "\u{1F1E9}\u{1F1F0}" : "\u{1F1EC}\u{1F1E7}" }}</span>
                        <span class="text-[14px] font-semibold text-bark">{{ app()->getLocale() === 'da' ? __('general.danish') : __('general.english') }}</span>
                        <a href="/settings" class="ml-auto text-[13px] font-semibold text-forest-400" wire:navigate>{{ __('general.change') }}</a>
                    </div>
                </div>

                {{-- Cover Image --}}
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.cover_image') }}</label>
                    <input type="file" wire:model="coverImage" accept="image/*" class="w-full text-xs text-muted" />
                    @error('coverImage') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- CTA --}}
            <div class="pb-4">
                <button wire:click="nextStep" class="flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm">
                    {{ __('general.next_add_checkpoints') }} &rarr;
                </button>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- STEP 2: Checkpoints --}}
    {{-- ============================================================ --}}
    @if ($step === 2)
        <div class="flex flex-1 flex-col">
            {{-- Forest header --}}
            <div class="bg-forest-600 px-4 pb-4 pt-2">
                <div class="flex items-center gap-2.5 pb-3 pt-1">
                    <button wire:click="previousStep" class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[11px] bg-white/15">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <div class="flex flex-1 gap-1">
                        @for ($i = 1; $i <= 4; $i++)
                            <div class="h-[3px] flex-1 rounded-[2px] {{ $i <= 2 ? 'bg-white' : 'bg-white/30' }}"></div>
                        @endfor
                    </div>
                    <span class="shrink-0 text-[11px] text-white/70">{{ __('general.step_of', ['current' => 2, 'total' => 4]) }}</span>
                </div>
                <h1 class="font-heading text-[22px] font-extrabold text-white">{{ __('general.add_checkpoints') }}</h1>
            </div>

            {{-- Map area --}}
            <div class="relative h-[280px] bg-[#E4EDE4]"
                x-data="{
                    map: null,
                    markers: [],
                    init() {
                        if (typeof google === 'undefined') return;
                        this.map = new google.maps.Map(this.$el, {
                            center: { lat: 55.6761, lng: 12.5683 },
                            zoom: 13,
                            mapTypeControl: false,
                            streetViewControl: false,
                        });
                        {{-- Place existing markers --}}
                        @foreach ($checkpoints as $cpIndex => $checkpoint)
                            @if ($checkpoint['latitude'] && $checkpoint['longitude'])
                                this.addMarker({{ $cpIndex }}, {{ $checkpoint['latitude'] }}, {{ $checkpoint['longitude'] }});
                            @endif
                        @endforeach
                        this.map.addListener('click', (e) => {
                            const nextIndex = this.markers.length;
                            $wire.addCheckpoint();
                            $wire.updateCheckpointCoordinates(nextIndex, e.latLng.lat(), e.latLng.lng());
                            this.addMarker(nextIndex, e.latLng.lat(), e.latLng.lng());
                        });
                    },
                    addMarker(index, lat, lng) {
                        const marker = new google.maps.Marker({
                            position: { lat, lng },
                            map: this.map,
                            draggable: true,
                            label: { text: String(index + 1), color: 'white', fontWeight: 'bold', fontSize: '12px' },
                        });
                        marker.addListener('dragend', (e) => {
                            $wire.updateCheckpointCoordinates(index, e.latLng.lat(), e.latLng.lng());
                        });
                        this.markers.push(marker);
                    }
                }"
            >
                <div class="flex h-full items-center justify-center text-sm text-gray-500">{{ __('general.loading') }}</div>
                {{-- Overlay hint --}}
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/40 to-transparent px-4 pb-3 pt-6">
                    <p class="text-center text-[13px] font-semibold text-white">{{ __('general.tap_map_to_add') }}</p>
                </div>
            </div>

            {{-- Checkpoint list --}}
            <div class="flex-1 overflow-y-auto px-4 pb-4 pt-4">
                <p class="mb-3 text-[10px] font-bold uppercase tracking-wide text-muted">
                    {{ __('general.checkpoints_added', ['count' => count($checkpoints)]) }}
                </p>

                <div class="flex flex-col gap-2.5">
                    @foreach ($checkpoints as $cpIndex => $checkpoint)
                        <div class="flex items-center gap-3 rounded-[12px] bg-white p-3 shadow-sm" wire:key="cp-{{ $cpIndex }}">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-forest-600 text-[11px] font-bold text-white">{{ $cpIndex + 1 }}</div>
                            <div class="min-w-0 flex-1">
                                <input
                                    type="text"
                                    wire:model="checkpoints.{{ $cpIndex }}.title"
                                    class="w-full border-none bg-transparent p-0 text-[13px] font-semibold text-bark placeholder-muted/50 focus:outline-none focus:ring-0"
                                    placeholder="{{ __('quests.title') }}"
                                />
                                @error("checkpoints.{$cpIndex}.title") <p class="text-[10px] text-coral">{{ $message }}</p> @enderror
                                @if ($checkpoint['latitude'] && $checkpoint['longitude'])
                                    <p class="text-[11px] text-muted">{{ number_format($checkpoint['latitude'], 4) }}, {{ number_format($checkpoint['longitude'], 4) }}</p>
                                @endif
                            </div>
                            @if (count($checkpoints) > 1)
                                <button wire:click="removeCheckpoint({{ $cpIndex }})" class="shrink-0 text-muted">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            @else
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
                            @endif
                        </div>
                    @endforeach

                    {{-- Add another --}}
                    <button wire:click="addCheckpoint" class="flex items-center gap-3 rounded-[12px] border-2 border-dashed border-cream-border p-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-dashed border-cream-border">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        </div>
                        <span class="text-[13px] font-semibold text-forest-400">{{ __('general.add_another_checkpoint') }}</span>
                    </button>
                </div>
            </div>

            {{-- CTA --}}
            <div class="border-t border-cream-border bg-white px-4 py-3">
                <button wire:click="nextStep" class="flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm">
                    {{ __('general.next_add_questions') }} &rarr;
                </button>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- STEP 3: Questions --}}
    {{-- ============================================================ --}}
    @if ($step === 3)
        <div class="flex flex-1 flex-col px-4">
            {{-- Header --}}
            <x-step-indicator :current="3" :total="4" back-action="previousStep" />

            @php
                $cpI = $activeCheckpointIndex;
                $qI = $activeQuestionIndex;
                $currentCheckpoint = $checkpoints[$cpI] ?? $checkpoints[0] ?? ['title' => '', 'latitude' => null, 'longitude' => null];
                $currentQuestions = $questions[$cpI] ?? [];
            @endphp

            <h1 class="font-heading text-[18px] font-extrabold text-bark">
                {{ __('general.stop_x_of_y', ['current' => $cpI + 1, 'total' => count($checkpoints)]) }}: {{ $currentCheckpoint['title'] ?: __('quests.checkpoint') . ' ' . ($cpI + 1) }}
            </h1>
            @if (!empty($currentQuestions))
                <p class="mb-3 mt-0.5 text-[13px] text-muted">{{ __('quests.question') }} {{ $qI + 1 }}</p>
            @endif

            {{-- Location confirmed banner --}}
            @if ($currentCheckpoint['latitude'] && $currentCheckpoint['longitude'])
                <div class="mb-4 flex items-center gap-2 rounded-[12px] bg-[#D4EDE4] px-[14px] py-[10px]">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" class="shrink-0 text-forest-600"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z" fill="currentColor"/></svg>
                    <div>
                        <p class="text-[12px] font-semibold text-forest-600">{{ __('general.location_confirmed') }}</p>
                        <p class="text-[11px] text-forest-600/70">{{ number_format($currentCheckpoint['latitude'], 5) }}, {{ number_format($currentCheckpoint['longitude'], 5) }}</p>
                    </div>
                </div>
            @endif

            <div class="flex-1 overflow-y-auto pb-4">
                @if (empty($currentQuestions))
                    <p class="py-8 text-center text-[13px] text-muted">{{ __('quests.no_questions') }}</p>
                @else
                    @foreach ($currentQuestions as $qIndex => $question)
                        <div class="mb-4 flex flex-col gap-3" wire:key="q-{{ $cpI }}-{{ $qIndex }}">
                            {{-- Question header --}}
                            <div class="flex items-center justify-between">
                                <span class="text-[12px] font-bold text-muted">{{ __('quests.question') }} {{ $qIndex + 1 }}</span>
                                <button wire:click="removeQuestion({{ $cpI }}, {{ $qIndex }})" class="text-[11px] font-semibold text-coral">{{ __('general.delete') }}</button>
                            </div>

                            {{-- Question body --}}
                            <textarea
                                wire:model="questions.{{ $cpI }}.{{ $qIndex }}.body"
                                rows="2"
                                class="w-full rounded-[12px] border-2 border-cream-border bg-white px-3.5 py-3 text-[13px] text-bark placeholder-muted/50 focus:border-forest-600 focus:outline-none focus:ring-0"
                                placeholder="{{ __('quests.question') }}..."
                            ></textarea>
                            @error("questions.{$cpI}.{$qIndex}.body") <p class="text-[10px] text-coral">{{ $message }}</p> @enderror

                            {{-- Answer type segmented control --}}
                            <div>
                                <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.answer_type') }}</label>
                                <div class="flex rounded-[11px] bg-cream-dark p-[3px]">
                                    <button
                                        type="button"
                                        wire:click="$set('questions.{{ $cpI }}.{{ $qIndex }}.type', '{{ \App\Enums\QuestionType::MultipleChoice->value }}')"
                                        class="flex-1 rounded-[9px] py-2 text-center text-[12px] font-semibold transition-all
                                            {{ $question['type'] === \App\Enums\QuestionType::MultipleChoice->value ? 'bg-white text-bark shadow-sm' : 'text-muted' }}"
                                    >
                                        {{ __('general.multiple_choice') }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="$set('questions.{{ $cpI }}.{{ $qIndex }}.type', '{{ \App\Enums\QuestionType::OpenText->value }}')"
                                        class="flex-1 rounded-[9px] py-2 text-center text-[12px] font-semibold transition-all
                                            {{ $question['type'] === \App\Enums\QuestionType::OpenText->value ? 'bg-white text-bark shadow-sm' : 'text-muted' }}"
                                    >
                                        {{ __('general.text_answer') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Answers (multiple choice) --}}
                            @if ($question['type'] !== \App\Enums\QuestionType::OpenText->value)
                                <div class="flex flex-col gap-2">
                                    @php $letters = ['A', 'B', 'C', 'D', 'E', 'F']; @endphp
                                    @foreach ($question['answers'] as $aIndex => $answer)
                                        <div
                                            class="flex items-center gap-2.5 rounded-[12px] border-[1.5px] px-3 py-2.5 transition-colors
                                                {{ $answer['is_correct']
                                                    ? 'border-[#22C55E] bg-[#F0FDF4]'
                                                    : 'border-cream-border bg-white' }}"
                                            wire:key="a-{{ $cpI }}-{{ $qIndex }}-{{ $aIndex }}"
                                        >
                                            {{-- Letter badge --}}
                                            <button
                                                type="button"
                                                wire:click="$set('questions.{{ $cpI }}.{{ $qIndex }}.answers.{{ $aIndex }}.is_correct', {{ $answer['is_correct'] ? 'false' : 'true' }})"
                                                class="flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded-[8px] text-[11px] font-bold
                                                    {{ $answer['is_correct']
                                                        ? 'bg-[#22C55E] text-white'
                                                        : 'bg-cream-dark text-muted' }}"
                                            >
                                                {{ $letters[$aIndex] ?? chr(65 + $aIndex) }}
                                            </button>
                                            <input
                                                type="text"
                                                wire:model="questions.{{ $cpI }}.{{ $qIndex }}.answers.{{ $aIndex }}.body"
                                                class="flex-1 border-none bg-transparent p-0 text-[13px] text-bark placeholder-muted/50 focus:outline-none focus:ring-0"
                                                placeholder="{{ __('quests.answer') }} {{ $letters[$aIndex] ?? chr(65 + $aIndex) }}"
                                                {{ $question['type'] === \App\Enums\QuestionType::TrueFalse->value ? 'disabled' : '' }}
                                            />
                                            @if ($question['type'] !== \App\Enums\QuestionType::TrueFalse->value && count($question['answers']) > 2)
                                                <button wire:click="removeAnswer({{ $cpI }}, {{ $qIndex }}, {{ $aIndex }})" class="shrink-0 text-muted">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if ($question['type'] === \App\Enums\QuestionType::MultipleChoice->value && count($question['answers']) < 6)
                                        <button wire:click="addAnswer({{ $cpI }}, {{ $qIndex }})" class="text-[12px] font-semibold text-forest-400">
                                            + {{ __('quests.answer') }}
                                        </button>
                                    @endif
                                </div>
                            @else
                                <p class="text-[12px] text-muted">{{ __('sessions.open_ended_note') }}</p>
                            @endif

                            {{-- Hint --}}
                            <input
                                type="text"
                                wire:model="questions.{{ $cpI }}.{{ $qIndex }}.hint"
                                class="w-full rounded-[12px] border-2 border-cream-border bg-white px-3.5 py-3 text-[13px] text-bark placeholder-muted/50 focus:border-forest-600 focus:outline-none focus:ring-0"
                                placeholder="{{ __('general.hint_optional') }}"
                            />
                        </div>
                    @endforeach
                @endif

                {{-- Add question button --}}
                <button wire:click="addQuestion({{ $cpI }})" class="mt-2 flex w-full items-center justify-center gap-2 rounded-[12px] border-2 border-dashed border-cream-border py-3 text-[13px] font-semibold text-forest-400">
                    + {{ __('general.add_question') }}
                </button>
            </div>

            {{-- Bottom CTAs --}}
            <div class="flex gap-3 pb-4">
                @if ($cpI < count($checkpoints) - 1)
                    <button
                        wire:click="$set('activeCheckpointIndex', {{ $cpI + 1 }})"
                        class="flex flex-1 items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm"
                    >
                        {{ __('general.next_stop') }} &rarr;
                    </button>
                @else
                    <button wire:click="nextStep" class="flex flex-1 items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm">
                        {{ __('general.next') }}: {{ __('general.quest_settings') }} &rarr;
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- STEP 4: Quest Settings --}}
    {{-- ============================================================ --}}
    @if ($step === 4)
        <div class="flex flex-1 flex-col px-4">
            {{-- Header --}}
            <x-step-indicator :current="4" :total="4" back-action="previousStep" />

            <h1 class="mb-5 font-heading text-[22px] font-extrabold text-bark">{{ __('general.quest_settings') }}</h1>

            <div class="flex flex-1 flex-col gap-5 overflow-y-auto pb-4">
                {{-- Visibility --}}
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.visibility') }}</label>
                    <div class="flex flex-col gap-2.5">
                        {{-- Public --}}
                        <button
                            type="button"
                            wire:click="$set('visibility', 'public')"
                            class="flex items-center gap-3 rounded-[14px] border-[1.5px] px-4 py-[14px] text-left transition-colors
                                {{ $visibility === 'public' ? 'border-forest-600 bg-[#F4FBF7]' : 'border-cream-border bg-white' }}"
                        >
                            <div class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full border-2 {{ $visibility === 'public' ? 'border-forest-600' : 'border-cream-border' }}">
                                @if ($visibility === 'public')
                                    <div class="h-2 w-2 rounded-full bg-forest-600"></div>
                                @endif
                            </div>
                            <div>
                                <p class="text-[14px] font-semibold text-bark">{{ __('general.public') }}</p>
                                <p class="text-[12px] text-muted">{{ __('general.public_description') }}</p>
                            </div>
                        </button>
                        {{-- Private --}}
                        <button
                            type="button"
                            wire:click="$set('visibility', 'private')"
                            class="flex items-center gap-3 rounded-[14px] border-[1.5px] px-4 py-[14px] text-left transition-colors
                                {{ $visibility === 'private' ? 'border-forest-600 bg-[#F4FBF7]' : 'border-cream-border bg-white' }}"
                        >
                            <div class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full border-2 {{ $visibility === 'private' ? 'border-forest-600' : 'border-cream-border' }}">
                                @if ($visibility === 'private')
                                    <div class="h-2 w-2 rounded-full bg-forest-600"></div>
                                @endif
                            </div>
                            <div>
                                <p class="text-[14px] font-semibold text-bark">{{ __('general.private') }}</p>
                                <p class="text-[12px] text-muted">{{ __('general.private_description') }}</p>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Play Modes (checkbox list) --}}
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.play_modes') }}</label>
                    <div class="flex flex-col gap-2">
                        @foreach (\App\Enums\PlayMode::cases() as $mode)
                            <button
                                type="button"
                                wire:click="$set('playMode', '{{ $mode->value }}')"
                                class="flex items-center gap-3 rounded-[12px] border-[1.5px] bg-white px-4 py-3 text-left transition-colors
                                    {{ $playMode === $mode->value ? 'border-forest-600' : 'border-cream-border' }}"
                            >
                                {{-- Check icon --}}
                                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md {{ $playMode === $mode->value ? 'bg-forest-600' : 'border-[1.5px] border-cream-border' }}">
                                    @if ($playMode === $mode->value)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                    @endif
                                </div>
                                <span class="text-[14px] font-semibold text-bark">{{ str_replace('_', ' ', ucfirst($mode->value)) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Scoring --}}
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.scoring_settings') }}</label>
                    <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white">
                        {{-- Speed bonus --}}
                        <div class="flex items-center justify-between border-b border-cream-border px-4 py-3.5">
                            <div>
                                <p class="text-[14px] font-semibold text-bark">{{ __('general.speed_bonus') }}</p>
                                <p class="text-[11px] text-muted">{{ __('general.speed_bonus_description') }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="$toggle('scoringSpeedBonus')"
                                class="relative h-[26px] w-[44px] shrink-0 rounded-[13px] transition-colors {{ $scoringSpeedBonus ? 'bg-forest-600' : 'bg-cream-border' }}"
                            >
                                <span class="absolute top-[2px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-all {{ $scoringSpeedBonus ? 'left-[20px]' : 'left-[2px]' }}"></span>
                            </button>
                        </div>
                        {{-- Wrong answer penalty --}}
                        <div class="flex items-center justify-between border-b border-cream-border px-4 py-3.5">
                            <div>
                                <p class="text-[14px] font-semibold text-bark">{{ __('general.wrong_answer_penalty') }}</p>
                                <p class="text-[11px] text-muted">{{ __('general.wrong_answer_penalty_description') }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="$toggle('scoringWrongPenalty')"
                                class="relative h-[26px] w-[44px] shrink-0 rounded-[13px] transition-colors {{ $scoringWrongPenalty ? 'bg-forest-600' : 'bg-cream-border' }}"
                            >
                                <span class="absolute top-[2px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-all {{ $scoringWrongPenalty ? 'left-[20px]' : 'left-[2px]' }}"></span>
                            </button>
                        </div>
                        {{-- Completion bonus --}}
                        <div class="flex items-center justify-between px-4 py-3.5">
                            <div>
                                <p class="text-[14px] font-semibold text-bark">{{ __('general.completion_bonus') }}</p>
                                <p class="text-[11px] text-muted">{{ __('general.completion_bonus_description') }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="$toggle('scoringCompletionBonus')"
                                class="relative h-[26px] w-[44px] shrink-0 rounded-[13px] transition-colors {{ $scoringCompletionBonus ? 'bg-forest-600' : 'bg-cream-border' }}"
                            >
                                <span class="absolute top-[2px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-all {{ $scoringCompletionBonus ? 'left-[20px]' : 'left-[2px]' }}"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CTA --}}
            <div class="pb-4">
                <button wire:click="nextStep" class="flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm">
                    {{ __('general.review_and_publish') }} &rarr;
                </button>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- STEP 5: Review & Publish --}}
    {{-- ============================================================ --}}
    @if ($step === 5)
        <div class="flex flex-1 flex-col px-4">
            <x-step-indicator :current="4" :total="4" back-action="previousStep" />

            <h1 class="mb-4 font-heading text-[22px] font-extrabold text-bark">{{ __('sessions.review_publish') }}</h1>

            <div class="flex flex-1 flex-col gap-2.5 overflow-y-auto pb-4">
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
                        <span class="font-normal normal-case text-forest-400">{{ count($checkpoints) }} {{ __('general.added') }}</span>
                    </div>
                    @foreach ($checkpoints as $cpIndex => $checkpoint)
                        <div class="flex items-center gap-2 border-b border-cream-border py-1.5 last:border-b-0" wire:key="review-cp-{{ $cpIndex }}">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-forest-600 text-[9px] font-bold text-white">{{ $cpIndex + 1 }}</div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-semibold text-bark">{{ $checkpoint['title'] }}</p>
                                <p class="text-[9px] text-muted">{{ count($questions[$cpIndex] ?? []) }} {{ __('quests.question') }}{{ count($questions[$cpIndex] ?? []) !== 1 ? 's' : '' }}</p>
                            </div>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
                        </div>
                    @endforeach
                </div>

                {{-- Settings Row --}}
                <div class="flex gap-2">
                    <div class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white p-2.5 text-center">
                        <div class="text-[9px] text-muted">{{ __('general.visibility') }}</div>
                        <div class="font-heading text-xs font-bold text-bark">{{ ucfirst($visibility) }}</div>
                    </div>
                    <div class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white p-2.5 text-center">
                        <div class="text-[9px] text-muted">{{ __('general.mode') }}</div>
                        <div class="font-heading text-xs font-bold text-bark">{{ ucfirst(str_replace('_', ' ', $playMode)) }}</div>
                    </div>
                    <div class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white p-2.5 text-center">
                        <div class="text-[9px] text-muted">{{ __('general.scoring') }}</div>
                        <div class="font-heading text-xs font-bold text-bark">{{ $scoringSpeedBonus ? 'Speed' : 'Standard' }}</div>
                    </div>
                </div>

                {{-- Publish --}}
                <button wire:click="publish" class="mt-1 flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm">
                    {{ __('quests.publish') }} &rarr;
                </button>
            </div>
        </div>
    @endif
</div>
