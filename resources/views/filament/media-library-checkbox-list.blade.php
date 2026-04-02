@php
    use Filament\Forms\View\FormsIconAlias;
    use Filament\Support\Enums\GridDirection;
    use Filament\Support\Icons\Heroicon;

    $fieldWrapperView = $getFieldWrapperView();
    $extraInputAttributeBag = $getExtraInputAttributeBag();
    $isHtmlAllowed = $isHtmlAllowed();
    $gridDirection = $getGridDirection() ?? GridDirection::Column;
    $isBulkToggleable = $isBulkToggleable();
    $isDisabled = $isDisabled();
    $isSearchable = $isSearchable();
    $statePath = $getStatePath();
    $options = $getOptions();
    $livewireKey = $getLivewireKey();
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');
    $itemsPerPage = 12;
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        x-data="{
            areAllCheckboxesChecked: false,
            checkboxListOptions: [],
            filteredCheckboxListOptions: [],
            visibleCheckboxListOptions: [],
            search: '',
            page: 1,
            itemsPerPage: {{ $itemsPerPage }},
            unsubscribeLivewireHook: null,
            init() {
                this.refreshCheckboxListOptions()
                this.$nextTick(() => {
                    this.checkIfAllCheckboxesAreChecked()
                })

                this.unsubscribeLivewireHook = Livewire.interceptMessage(({ message, onSuccess }) => {
                    onSuccess(() => {
                        this.$nextTick(() => {
                            if (message.component.id !== @js($this->getId())) {
                                return
                            }

                            this.refreshCheckboxListOptions()
                            this.checkIfAllCheckboxesAreChecked()
                        })
                    })
                })

                this.$watch('search', () => {
                    this.page = 1
                    this.updateVisibleCheckboxListOptions()
                    this.checkIfAllCheckboxesAreChecked()
                })
            },
            refreshCheckboxListOptions() {
                this.checkboxListOptions = Array.from(this.$root.querySelectorAll('.fi-fo-checkbox-list-option-ctn'))
                this.updateVisibleCheckboxListOptions()
            },
            updateVisibleCheckboxListOptions() {
                this.filteredCheckboxListOptions = this.checkboxListOptions.filter((checkboxLabel) => {
                    if (['', null, undefined].includes(this.search)) {
                        return true
                    }

                    return checkboxLabel
                        .querySelector('.fi-fo-checkbox-list-option-label')
                        ?.innerText.toLowerCase()
                        .includes(this.search.toLowerCase()) || checkboxLabel
                        .querySelector('.fi-fo-checkbox-list-option-description')
                        ?.innerText.toLowerCase()
                        .includes(this.search.toLowerCase())
                })

                const totalPages = this.getTotalPages()

                if (this.page > totalPages) {
                    this.page = totalPages
                }

                if (this.page < 1) {
                    this.page = 1
                }

                const startIndex = (this.page - 1) * this.itemsPerPage
                this.visibleCheckboxListOptions = this.filteredCheckboxListOptions.slice(
                    startIndex,
                    startIndex + this.itemsPerPage,
                )
            },
            getTotalPages() {
                return Math.max(1, Math.ceil(this.filteredCheckboxListOptions.length / this.itemsPerPage))
            },
            visiblePageNumbers() {
                const totalPages = this.getTotalPages()
                let start = Math.max(1, this.page - 2)
                let end = Math.min(totalPages, start + 4)

                start = Math.max(1, end - 4)

                const pages = []

                for (let pageNumber = start; pageNumber <= end; pageNumber++) {
                    pages.push(pageNumber)
                }

                return pages
            },
            goToPage(pageNumber) {
                if (pageNumber < 1 || pageNumber > this.getTotalPages() || pageNumber === this.page) {
                    return
                }

                this.page = pageNumber
                this.updateVisibleCheckboxListOptions()
                this.checkIfAllCheckboxesAreChecked()
            },
            goToPreviousPage() {
                if (this.page <= 1) {
                    return
                }

                this.page--
                this.updateVisibleCheckboxListOptions()
                this.checkIfAllCheckboxesAreChecked()
            },
            goToNextPage() {
                if (this.page >= this.getTotalPages()) {
                    return
                }

                this.page++
                this.updateVisibleCheckboxListOptions()
                this.checkIfAllCheckboxesAreChecked()
            },
            currentPageStart() {
                if (! this.filteredCheckboxListOptions.length) {
                    return 0
                }

                return ((this.page - 1) * this.itemsPerPage) + 1
            },
            currentPageEnd() {
                return Math.min(this.page * this.itemsPerPage, this.filteredCheckboxListOptions.length)
            },
            checkIfAllCheckboxesAreChecked() {
                this.areAllCheckboxesChecked =
                    this.visibleCheckboxListOptions.length > 0 &&
                    this.visibleCheckboxListOptions.length ===
                    this.visibleCheckboxListOptions.filter((checkboxLabel) =>
                        checkboxLabel.querySelector('input[type=checkbox]:checked, input[type=checkbox]:disabled')
                    ).length
            },
            toggleAllCheckboxes() {
                this.checkIfAllCheckboxesAreChecked()

                const inverseAreAllCheckboxesChecked = ! this.areAllCheckboxesChecked

                this.visibleCheckboxListOptions.forEach((checkboxLabel) => {
                    const checkbox = checkboxLabel.querySelector('input[type=checkbox]')

                    if (checkbox.disabled || checkbox.checked === inverseAreAllCheckboxesChecked) {
                        return
                    }

                    checkbox.checked = inverseAreAllCheckboxesChecked
                    checkbox.dispatchEvent(new Event('change'))
                })

                this.areAllCheckboxesChecked = inverseAreAllCheckboxesChecked
            },
            destroy() {
                this.unsubscribeLivewireHook?.()
            },
        }"
        {{ $getExtraAlpineAttributeBag()->class(['fi-fo-checkbox-list']) }}
    >
        @if (! $isDisabled)
            @if ($isSearchable)
                <x-filament::input.wrapper
                    inline-prefix
                    :prefix-icon="Heroicon::MagnifyingGlass"
                    :prefix-icon-alias="FormsIconAlias::COMPONENTS_CHECKBOX_LIST_SEARCH_FIELD"
                    class="fi-fo-checkbox-list-search-input-wrp"
                >
                    <input
                        placeholder="{{ $getSearchPrompt() }}"
                        type="search"
                        x-model.debounce.{{ $getSearchDebounce() }}="search"
                        class="fi-input fi-input-has-inline-prefix"
                    />
                </x-filament::input.wrapper>
            @endif

            @if ($isBulkToggleable && count($options))
                <div
                    x-cloak
                    class="fi-fo-checkbox-list-actions"
                    wire:key="{{ $livewireKey }}.actions"
                >
                    <span
                        x-show="! areAllCheckboxesChecked && visibleCheckboxListOptions.length"
                        x-on:click="toggleAllCheckboxes()"
                        wire:key="{{ $livewireKey }}.actions.select-all"
                    >
                        {{ $getAction('selectAll') }}
                    </span>

                    <span
                        x-show="areAllCheckboxesChecked && visibleCheckboxListOptions.length"
                        x-on:click="toggleAllCheckboxes()"
                        wire:key="{{ $livewireKey }}.actions.deselect-all"
                    >
                        {{ $getAction('deselectAll') }}
                    </span>
                </div>
            @endif
        @endif

        <div
            {{
                $getExtraAttributeBag()
                    ->grid($getColumns(), $gridDirection)
                    ->merge([
                        'x-show' => 'visibleCheckboxListOptions.length',
                    ], escape: false)
                    ->class([
                        'fi-fo-checkbox-list-options',
                    ])
            }}
        >
            @forelse ($options as $value => $label)
                <div
                    wire:key="{{ $livewireKey }}.options.{{ $value }}"
                    x-show="visibleCheckboxListOptions.includes($el)"
                    class="fi-fo-checkbox-list-option-ctn"
                    style="height:100%;"
                >
                    <label
                        class="fi-fo-checkbox-list-option"
                        style="height:100%;align-items:flex-start;"
                    >
                        <input
                            type="checkbox"
                            {{
                                $extraInputAttributeBag
                                    ->merge([
                                        'disabled' => $isDisabled || $isOptionDisabled($value, $label),
                                        'value' => $value,
                                        'wire:loading.attr' => 'disabled',
                                        $wireModelAttribute => $statePath,
                                        'x-on:change' => $isBulkToggleable ? 'checkIfAllCheckboxesAreChecked()' : null,
                                    ], escape: false)
                                    ->class([
                                        'fi-checkbox-input',
                                        'fi-valid' => ! $errors->has($statePath),
                                        'fi-invalid' => $errors->has($statePath),
                                    ])
                            }}
                        />

                        <div class="fi-fo-checkbox-list-option-text" style="width:100%;height:100%;">
                            <span class="fi-fo-checkbox-list-option-label">
                                @if ($isHtmlAllowed)
                                    {!! $label !!}
                                @else
                                    {{ $label }}
                                @endif
                            </span>

                            @if ($hasDescription($value))
                                <p class="fi-fo-checkbox-list-option-description">
                                    {{ $getDescription($value) }}
                                </p>
                            @endif
                        </div>
                    </label>
                </div>
            @empty
                <div wire:key="{{ $livewireKey }}.empty"></div>
            @endforelse
        </div>

        <div
            x-cloak
            x-show="filteredCheckboxListOptions.length"
            style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.75rem;margin-top:1rem;"
        >
            <div style="color:#64748b;font-size:.82rem;text-align:center;">
                Showing <span x-text="currentPageStart()"></span>-<span x-text="currentPageEnd()"></span>
                of <span x-text="filteredCheckboxListOptions.length"></span>
            </div>

            <div style="display:flex;align-items:center;justify-content:center;gap:.75rem;flex-wrap:wrap;width:100%;">
                <button
                    type="button"
                    x-on:click="goToPreviousPage()"
                    x-bind:disabled="page <= 1"
                    style="border:1px solid rgba(148,163,184,.35);border-radius:.75rem;background:#fff;padding:.45rem .8rem;font-size:.82rem;font-weight:600;color:#0f172a;"
                >
                    Previous
                </button>

                <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;justify-content:center;">
                    <template x-for="pageNumber in visiblePageNumbers()" :key="`page-${pageNumber}`">
                        <button
                            type="button"
                            x-on:click="goToPage(pageNumber)"
                            x-text="pageNumber"
                            x-bind:disabled="pageNumber === page"
                            x-bind:style="
                                `border:1px solid ${pageNumber === page ? 'rgba(15,23,42,.9)' : 'rgba(148,163,184,.35)'};
                                border-radius:.75rem;
                                background:${pageNumber === page ? '#0f172a' : '#fff'};
                                padding:.45rem .7rem;
                                font-size:.82rem;
                                font-weight:600;
                                color:${pageNumber === page ? '#fff' : '#0f172a'};
                                min-width:2.25rem;`
                            "
                        ></button>
                    </template>
                </div>

                <button
                    type="button"
                    x-on:click="goToNextPage()"
                    x-bind:disabled="page >= getTotalPages()"
                    style="border:1px solid rgba(148,163,184,.35);border-radius:.75rem;background:#fff;padding:.45rem .8rem;font-size:.82rem;font-weight:600;color:#0f172a;"
                >
                    Next
                </button>
            </div>
        </div>

        @if ($isSearchable)
            <div
                x-cloak
                x-show="search && ! filteredCheckboxListOptions.length"
                class="fi-fo-checkbox-list-no-search-results-message"
            >
                {{ $getNoSearchResultsMessage() }}
            </div>
        @endif
    </div>
</x-dynamic-component>
