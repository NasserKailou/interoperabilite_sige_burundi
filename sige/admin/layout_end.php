<?php /* Fermeture du layout AdminLTE */ ?>
        </div><!-- /.container-fluid -->
    </div><!-- /.content -->
</div><!-- /.content-wrapper -->

<!-- ─── FOOTER ─── -->
<footer class="main-footer">
    <strong>&copy; <?= date('Y') ?> <a href="#">République du Burundi — SIGE</a></strong>
    <div class="float-right d-none d-sm-inline-block">
        <b>Version</b> <?= APP_VERSION ?> &bull;
        <b>Mode données</b> <span class="badge badge-sige-mock"><?= e(DATA_SOURCE_MODE) ?></span>
    </div>
</footer>

</div><!-- /.wrapper -->

<!-- Scripts AdminLTE -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Utilitaires partagés admin
function fmtNum(n, d=0) {
    if(n===null||n===undefined) return '—';
    return Number(n).toLocaleString('fr-FR',{minimumFractionDigits:d,maximumFractionDigits:d});
}
function fmtPct(n, d=1) {
    if(n===null||n===undefined) return '—';
    return fmtNum(n,d)+'\u202f%';
}
function escHtml(s) {
    if(!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
<?php if (!empty($pageScript)): ?>
<script><?= $pageScript ?></script>
<?php endif; ?>
</body>
</html>
