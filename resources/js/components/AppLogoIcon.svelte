<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    class={className}
    aria-hidden="true"
    {...rest}
>
    <path
        fill="currentColor"
        fill-rule="evenodd"
        clip-rule="evenodd"
        d="M7 1.5h6.9a2 2 0 0 1 1.42.59l3.59 3.59a2 2 0 0 1 .59 1.42V21a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5.5 21V3A1.5 1.5 0 0 1 7 1.5Zm6 1.5v4h4L13 3ZM8 10.25h8v1.5H8v-1.5Zm0 4.25h8v1.5H8v-1.5Zm0 4.25h5v1.5H8v-1.5Z"
    />
</svg>
