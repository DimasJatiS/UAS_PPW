<section id="contact-section" class="customize-contact-section py-5 section-custom-order-effect">
            <div class="container">
                <a href="kostum_order.php" class="customize-title-link text-decoration-none d-block text-center">
                    <h2 class="customize-title mb-5">
                        <i class="fas fa-cut me-2"></i>
                        Costumize Your Own
                    </h2>
                </a>
                <div class="row align-items-center justify-content-center mt-4">
                    <div class="col-lg-4 text-center mb-4 mb-lg-0 contact-image-container">
                        <img src="assets/custom-red.jpg" alt="Customize Flower Arrangement" class="img-fluid contact-image-custom">
                    </div>
                    <div class="col-lg-6 offset-lg-1">
                        <div class="contact-form-and-links-container form-with-bg-icon p-4 p-md-5">
                            <h4 class="text-center mb-4" style="font-family: 'Cooper Black'; font-size: 2rem;">Send Us Messages</h4>
                            <?php if ($index_contact_error): ?><div class="alert alert-danger" role="alert"><?php echo $index_contact_error; ?></div><?php endif; ?>
                            <?php if ($index_contact_success): ?><div class="alert alert-success" role="alert"><?php echo htmlspecialchars($index_contact_success); ?></div><?php endif; ?>

                            <form id="indexContactForm" action="proses_kontak.php" method="POST" class="mb-4">
                                <input type="hidden" name="source_page" value="index_form_submit">
                                <?php if ($is_any_user_logged_in && ($current_fullname || $current_username)): ?>
                                    <input type="hidden" name="nama_pengirim" value="<?php echo htmlspecialchars($current_fullname ?: $current_username); ?>">
                                <?php endif; ?>
                                <input type="hidden" name="subjek_pesan" value="Pesan dari Halaman Utama (Customize Section)">
                                <div class="mb-3">
                                    <input type="email" name="email_pengirim" class="form-control form-control-lg contact-input contact-input-email" placeholder="Your e-mail" required value="<?php echo htmlspecialchars($form_data_idx['email_pengirim'] ?? $default_email_idx); ?>">
                                </div>
                                <div class="mb-4 message-field-wrapper">
                                    <textarea class="form-control form-control-lg contact-input contact-input-message" name="isi_pesan" rows="4" placeholder="Message" required><?php echo htmlspecialchars($form_data_idx['isi_pesan'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-submit btn-lg">Submit Message!</button>
                            </form>
                            <nav class="nav justify-content-center flex-wrap mb-3 footer-nav-links">
                                <a class="nav-link footer-link" href="index.php">Home</a>
                                <a class="nav-link footer-link" href="about.php">About</a>
                                <a class="nav-link footer-link" href="gallery_page.php">Gallery</a>
                                <a class="nav-link footer-link" href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Contact</a>
                            </nav>
                            <div class="social-icons text-center">
                                <a href="https://www.facebook.com/profile.php?id=100006681615415" target="_blank" class="social-icon-link mx-2"><i class="fab fa-facebook-f"></i></a>
                                <a href="https://www.threads.com/@dimaassatriaa1" target="_blank" class="social-icon-link mx-2"><i class="fab fa-threads"></i></a>
                                <a href="https://www.instagram.com/dimaassatriaa1" target="_blank" class="social-icon-link mx-2"><i class="fab fa-instagram"></i></a>
                                <a href="https://www.linkedin.com/in/dimas-jati-satria-26a794221/" target="_blank" class="social-icon-link mx-2"><i class="fab fa-linkedin"></i></a>
                                <a href="https://www.youtube.com/channel/UChZ_qhLgmlOOPfN8LJeAByw" target="_blank" class="social-icon-link mx-2"><i class="fab fa-youtube"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer class="actual-footer py-3 bg-light">
            <div class="container text-center">
                <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> BLOOMARIE. All Rights Reserved.</p>
            </div>
        </footer>
    </div>

    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title internal-page-header" id="contactModalLabel" style="margin-bottom: 0;">Contact Us</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contactModalAlerts" class="mb-3"></div>
                    <form id="contactModalForm">
                        <input type="hidden" name="source_page" value="modal_contact_ajax">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_nama_pengirim" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control contact-input" id="modal_nama_pengirim" name="nama_pengirim" required
                                       value="<?php echo $is_any_user_logged_in ? htmlspecialchars($current_fullname ?: $current_username) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_email_pengirim" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control contact-input" id="modal_email_pengirim" name="email_pengirim" required
                                       value="<?php echo $is_any_user_logged_in ? htmlspecialchars($current_email) : ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_subjek_pesan" class="form-label">Subject</label>
                            <input type="text" class="form-control contact-input" id="modal_subjek_pesan" name="subjek_pesan">
                        </div>
                        <div class="mb-3">
                            <label for="modal_isi_pesan" class="form-label">The Messages <span class="text-danger">*</span></label>
                            <textarea class="form-control contact-input" id="modal_isi_pesan" name="isi_pesan" rows="4" style="border-radius: 20px !important;" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-submit">Send Messages</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        <?php if ($is_any_user_logged_in): ?>
            const defaultNameModal = "<?php echo htmlspecialchars(addslashes($current_fullname ?: $current_username)); ?>";                        
            const defaultEmailModal = "<?php echo htmlspecialchars(addslashes($current_email)); ?>";
            if(document.getElementById('modal_nama_pengirim')) document.getElementById('modal_nama_pengirim').value = defaultNameModal;                                
            if(document.getElementById('modal_email_pengirim')) document.getElementById('modal_email_pengirim').value = defaultEmailModal;
        <?php endif; ?>
    </script>