<form wire:submit="submit" class="space-y-4">
   {!! $this->getHtmlFormsHiddenInputs('contacto') !!}
   {{ $slot }}

   @if (config('livewire-html-forms.turnstile.enabled'))
      <div>
         <x-livewire-html-forms::turnstile wire:model="turnstileResponse" :id="$this->formSlug . '-' . $this->getId()" />
      </div>
   @endif

   {{ $submit ?? '' }}
</form>
