<script>
    (() => {
        const expandActiveNavigationGroups = () => {
            const sidebar = window.Alpine?.store('sidebar');

            if (! sidebar?.collapsedGroups) {
                return;
            }

            document.querySelectorAll('.fi-sidebar-group.fi-active').forEach((group) => {
                const label = group.dataset.groupLabel;

                if (label && sidebar.groupIsCollapsed(label)) {
                    sidebar.toggleCollapsedGroup(label);
                }
            });
        };

        document.addEventListener('alpine:initialized', () => {
            expandActiveNavigationGroups();
        });

        document.addEventListener('livewire:navigated', () => {
            expandActiveNavigationGroups();
        });
    })();
</script>
