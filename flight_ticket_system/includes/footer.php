<footer class="border-top py-3">
  <div class="container text-center small text-muted">
    &copy; <?= date('Y') ?> Flight Ticket System
    <br>
    <?php
    // ========= Visitor Counter =========
    $counter_file = __DIR__ . '/../counter.txt';

    // ถ้าไฟล์ยังไม่เคยสร้าง ให้สร้างใหม่และตั้งค่าเริ่มต้นเป็น 0
    if (!file_exists($counter_file)) {
      file_put_contents($counter_file, 0);
    }

    // อ่านค่าปัจจุบัน แล้ว +1
    $visitor_count = (int) file_get_contents($counter_file);
    $visitor_count++;
    file_put_contents($counter_file, $visitor_count);
    ?>

    👁️ จำนวนผู้เข้าชมเว็บไซต์: 
    <strong><?= number_format($visitor_count) ?></strong> ครั้ง
  </div>
</footer>

<!-- Bootstrap Bundle JS: CDN + สำรองโลคอล -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-+vZ8w1kB2m1a0l3hI3M3vv7q8Jx1d2QX1tX3w6+2m2y4Ckz2N9o4wG3Wm9c4KkG5"
        crossorigin="anonymous"></script>
<script src="<?= $ROOT_URL ?>assets/vendor/bootstrap.bundle.min.js"></script>
<script src="<?= $ROOT_URL ?>assets/js/app.js"></script>

</body>
</html>

</footer>


</body>
</html>
