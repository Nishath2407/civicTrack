<?php
/**
 * CivicTrack — includes/footer.php
 */
?>
<footer class="site-footer">
    <div class="footer-inner">
        <span class="footer-logo">CivicTrack</span>
        <span class="footer-sep">|</span>
        <span>&copy; <?= date('Y') ?> All Rights Reserved</span>
        
        <span class="footer-sep">|</span>
        <a href="<?= APP_URL ?>/admin/" style="font-weight: bold; color: var(--teal-light);">Admin Portal</a>
    </div> </footer>

<div class="toast-container" id="toastContainer"></div>

<script>
function showToast(type, msg) {
  const c = document.getElementById('toastContainer');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => { 
    t.style.opacity='0'; 
    t.style.transform='translateX(50px)'; 
    t.style.transition='.4s'; 
    setTimeout(()=>t.remove(),400); 
  }, 3500);
}
</script>
</body>
</html>