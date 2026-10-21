<script>
    Chart.defaults.font.family = "'Poppins', sans-serif";
    document.fonts.load('12px Poppins').then(() => {
        Object.values(Chart.instances).forEach(chart => chart.update('none'));
    });
</script>
