<div class="error">
    <?php if (! $this->errors): ?>
        <p><?php echo $this->locale('TEXT_NO_ERRORS'); ?></p>
    <?php else: ?>
        <ul>
            <?php
                foreach ((array) $this->errors as $err) {
                    echo "<li>";
                    if ($err instanceof Exception) {
                        echo "<pre>";
                        echo $this->escape($err->__toString());
                        echo "</pre>";
                    } else {
                        echo $this->locale($err);
                    }
                    echo "</li>\n";
                }
            ?>
        </ul>
    <?php endif; ?>
</div>
