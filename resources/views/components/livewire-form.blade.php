<form wire:submit="submit" class="space-y-4">
   {!! $this->getHtmlFormsHiddenInputs('contacto') !!}
   {{ $slot }}

   <div wire:ignore>
      <x-livewire-html-forms::turnstile wire:model="turnstileResponse" :id="'_' . $this->getId()" />
   </div>

   {{ $submit ?? '' }}
</form>
