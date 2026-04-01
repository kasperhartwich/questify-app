@props([
    'wireModel' => '',
    'length' => 6,
    'inputmode' => 'text',
])

<div
    x-data="{
        code: @entangle($wireModel),
        length: {{ $length }},
        boxes: Array({{ $length }}).fill(''),
        focusIndex: 0,
        init() {
            this.syncFromCode()
            this.$watch('code', () => this.syncFromCode())
        },
        syncFromCode() {
            const chars = (this.code || '').split('')
            this.boxes = Array(this.length).fill('').map((_, i) => chars[i] || '')
            this.focusIndex = Math.min(chars.length, this.length - 1)
        },
        handleInput(index, event) {
            const val = event.target.value.slice(-1).toUpperCase()
            this.boxes[index] = val
            this.code = this.boxes.join('')
            if (val && index < this.length - 1) {
                this.$refs['box' + (index + 1)].focus()
            }
        },
        handleKeydown(index, event) {
            if (event.key === 'Backspace' && !this.boxes[index] && index > 0) {
                this.boxes[index - 1] = ''
                this.code = this.boxes.join('')
                this.$refs['box' + (index - 1)].focus()
            }
        },
        handlePaste(event) {
            event.preventDefault()
            const pasted = (event.clipboardData.getData('text') || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, this.length)
            pasted.split('').forEach((ch, i) => { this.boxes[i] = ch })
            this.code = this.boxes.join('')
            const nextIndex = Math.min(pasted.length, this.length - 1)
            this.$refs['box' + nextIndex].focus()
        },
    }"
    class="flex justify-center gap-[8px]"
>
    @for ($i = 0; $i < $length; $i++)
        <input
            type="text"
            maxlength="1"
            inputmode="{{ $inputmode }}"
            x-ref="box{{ $i }}"
            :value="boxes[{{ $i }}]"
            @input="handleInput({{ $i }}, $event)"
            @keydown="handleKeydown({{ $i }}, $event)"
            @paste="handlePaste($event)"
            @focus="focusIndex = {{ $i }}"
            :class="{
                'border-forest-600 bg-[#F0FAF5]': boxes[{{ $i }}],
                'border-amber-400 bg-white': !boxes[{{ $i }}] && focusIndex === {{ $i }},
                'border-cream-border bg-white': !boxes[{{ $i }}] && focusIndex !== {{ $i }},
            }"
            class="flex h-[58px] w-0 min-w-0 flex-1 items-center justify-center rounded-[13px] border-2 text-center font-heading text-2xl font-extrabold text-bark focus:outline-none"
        />
    @endfor
</div>
