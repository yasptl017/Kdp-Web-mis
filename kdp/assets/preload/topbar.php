<?php
/* =====================================
   FETCH COLLEGE TOP BAR DETAILS
===================================== */

$college = [
    'contact_no'     => '',
    'email'          => '',
    'facebook_link'  => '',
    'twitter_link'   => '',
    'linkedin_link'  => '',
    'instagram_link' => ''
];

$query = $conn->query("
    SELECT 
        contact_no,
        email,
        facebook_link,
        twitter_link,
        linkedin_link,
        instagram_link
    FROM college_details
    ORDER BY id DESC
    LIMIT 1
");

if ($query && $query->num_rows > 0) {
    $college = $query->fetch_assoc();
}
?>

<!-- Top Info Bar -->
<div class="top-info-bar">
    <div class="container">
        <div class="row align-items-center">

            <!-- LEFT SIDE -->
            <div class="col-md-8">
                <ul class="top-info-list">

                    <?php if (!empty($college['contact_no'])): ?>
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <a href="tel:<?php echo htmlspecialchars($college['contact_no']); ?>">
                            <?php echo htmlspecialchars($college['contact_no']); ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($college['email'])): ?>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?php echo htmlspecialchars($college['email']); ?>">
                            <?php echo htmlspecialchars($college['email']); ?>
                        </a>
                    </li>
                    <?php endif; ?>

                </ul>
            </div>

            <!-- RIGHT SIDE -->
            <div class="col-md-4 text-end d-none d-md-block">
                <div class="social-links-top">

                    <?php if (!empty($college['facebook_link'])): ?>
                        <a href="<?php echo htmlspecialchars($college['facebook_link']); ?>" target="_blank" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($college['twitter_link'])): ?>
                        <a href="<?php echo htmlspecialchars($college['twitter_link']); ?>" target="_blank" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($college['linkedin_link'])): ?>
                        <a href="<?php echo htmlspecialchars($college['linkedin_link']); ?>" target="_blank" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($college['instagram_link'])): ?>
                        <a href="<?php echo htmlspecialchars($college['instagram_link']); ?>" target="_blank" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>
