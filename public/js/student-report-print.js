(() => {
    if (window.edlinkStudentReportPrint) return;
    window.edlinkStudentReportPrint = true;

    const cleanup = () => {
        document.getElementById('student-report-print-root')?.remove();
        document.body.classList.remove('printing-student-reports');
    };

    window.addEventListener('beforeprint', () => {
        cleanup();
        const reports = [...document.querySelectorAll('[data-student-report]')];
        if (!reports.length) return;

        const root = document.createElement('div');
        root.id = 'student-report-print-root';
        document.body.append(root);
        document.body.classList.add('printing-student-reports');

        for (const report of reports) {
            const sheet = document.createElement('div');
            sheet.className = 'student-report-sheet';
            const content = report.cloneNode(true);
            content.removeAttribute('id');
            content.removeAttribute('data-student-report');
            content.querySelectorAll('[id]').forEach(node => node.removeAttribute('id'));
            content.classList.add('student-report-print-content');
            sheet.append(content);
            root.append(sheet);

            // Measure the complete compact card, including wrapped remarks and
            // optional sections. Reserve a pixel against print rounding.
            const scale = Math.min(1,
                (sheet.clientWidth - 1) / Math.max(content.scrollWidth, content.offsetWidth),
                (sheet.clientHeight - 1) / Math.max(content.scrollHeight, content.offsetHeight));
            content.style.transform = `scale(${scale})`;
        }
    });

    window.addEventListener('afterprint', cleanup);
    document.addEventListener('livewire:navigating', cleanup);
})();
