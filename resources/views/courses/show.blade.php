<x-app-layout>
    <livewire:course-viewer :course="$course" :initialLessonId="request()->integer('lesson') ?: null" />
</x-app-layout>
