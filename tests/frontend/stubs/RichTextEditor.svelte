<script lang="ts">
    let { value = '' }: { value?: string; onchange?: (html: string) => void } = $props();
</script>

<textarea data-testid="richtexteditor" value={value}></textarea>