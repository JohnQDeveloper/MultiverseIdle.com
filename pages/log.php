<?php require_once('../templates/game-header.php'); ?>
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('log.title'); ?></h1>

        <h3><?php echo t('log.wire_title'); ?></h3>
        <?php if (empty($wireLog)): ?>
        <p><?php echo t('log.no_wires'); ?></p>
        <?php else: ?>
        <table class="log-table">
            <thead>
                <tr>
                    <th><?php echo t('log.col.date'); ?></th>
                    <th><?php echo t('log.col.from'); ?></th>
                    <th><?php echo t('log.col.to'); ?></th>
                    <th><?php echo t('log.col.amount'); ?></th>
                    <th><?php echo t('log.col.commodity'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($wireLog as $entry): ?>
                <tr>
                    <td><?php echo htmlspecialchars($entry['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($entry['sender_username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($entry['recipient_username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format((int)$entry['amount']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($entry['commodity']), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
