<!DOCTYPE html>
<html lang="en" x-data="{ dark: localStorage.getItem('edlink_theme') === 'dark' || (!localStorage.getItem('edlink_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
      x-init="$watch('dark', v => { localStorage.setItem('edlink_theme', v ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', v); }); document.documentElement.classList.toggle('dark', dark);"
      :class="{ 'dark': dark }">
<head><link rel="icon" type="image/png" href="{{ asset('img/fav.png') }}"><link rel="apple-touch-icon" href="{{ asset('img/fav.png') }}">
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Terms and Conditions - Edlink</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<script>
		tailwind.config = {
			darkMode: 'class',
			theme: {
				extend: {
					fontFamily: { sans: ['Poppins', 'sans-serif'] },
					colors: { darken: '#161327', ink: '#0f0d1f' },
				}
			}
		}
	</script>
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<style>
		[x-cloak] { display: none !important; }
		html { scroll-behavior: smooth; }
		body { font-family: 'Poppins', sans-serif; }
		.glass { backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
		.prose-legal h2 { scroll-margin-top: 6rem; }
	</style>
</head>
<body class="antialiased bg-white dark:bg-ink text-gray-700 dark:text-gray-300 transition-colors duration-300">

	<div class="fixed top-0 inset-x-0 z-40 glass bg-white/70 dark:bg-ink/70 border-b border-gray-100 dark:border-white/10">
		<div class="max-w-7xl mx-auto px-6 flex items-center justify-between h-16">
			<a href="{{ url('/') }}" class="flex items-center space-x-2">
				<span class=" shrink-0">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
            <img src="{{ asset('img/logoneg.png') }}" alt="Edlink logo" class="w-[150px] h-auto">
            </a>
            </span>
			<div class="flex items-center space-x-3">
				<button @click="dark = !dark" class="w-9 h-9 rounded-full flex items-center justify-center border border-gray-200 dark:border-white/10 hover:border-yellow-400 transition-colors">
					<svg x-show="!dark" class="w-4 h-4 text-darken" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
					<svg x-show="dark" x-cloak class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 24 24"><path d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 008.998-5.998z"/></svg>
				</button>
				<a href="{{ url('/') }}" class="px-5 py-2 text-sm font-medium rounded-full border border-gray-200 dark:border-white/15 hover:border-yellow-400 transition-colors">Back home</a>
			</div>
		</div>
	</div>

	<div class="max-w-7xl mx-auto px-6 pt-32 pb-24 grid lg:grid-cols-4 gap-12">
		<aside class="hidden lg:block lg:col-span-1">
			<div class="sticky top-28">
				<p class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">On this page</p>
				<nav class="space-y-2 text-sm">
					<a href="#s1" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">1. Acceptance of Terms</a>
					<a href="#s2" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">2. Service Description</a>
					<a href="#s3" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">3. Accounts and Responsibility</a>
					<a href="#s4" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">4. Trials and Availability</a>
					<a href="#s5" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">5. Fees and Payment</a>
					<a href="#s6" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">6. Student, Guardian and Staff Data</a>
					<a href="#s7" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">7. Acceptable Use</a>
					<a href="#s8" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">8. Data Retention and Deletion</a>
					<a href="#s9" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">9. Service Availability</a>
					<a href="#s10" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">10. Intellectual Property</a>
					<a href="#s11" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">11. Termination</a>
					<a href="#s12" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">12. Limitation of Liability</a>
					<a href="#s13" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">13. Indemnification</a>
					<a href="#s14" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">14. Changes to Terms</a>
					<a href="#s15" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">15. Governing Law</a>
					<a href="#s16" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">16. Contact</a>
				</nav>
			</div>
		</aside>

		<div class="lg:col-span-3">
			<span class="text-xs font-bold tracking-widest text-yellow-500 uppercase">Legal</span>
			<h1 class="mt-3 text-3xl sm:text-4xl font-extrabold text-darken dark:text-white">Terms and Conditions</h1>
			<p class="mt-3 text-gray-500 dark:text-gray-400">Last updated: {{ now()->format('d F Y') }}</p>

			<div class="mt-6 rounded-2xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-5 py-4 text-sm text-amber-800 dark:text-amber-300">
				These Terms form a binding agreement between you and Spotnet Technologies. If you use Edlink on behalf of a school or organization, you confirm that you have authority to bind that entity.
			</div>

			<div class="prose-legal mt-10 space-y-10 text-sm leading-relaxed">

				<section id="s1">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">1. Acceptance of Terms</h2>
					<p>By creating an account, registering a school, or otherwise accessing or using Edlink (the "Service"), you agree to be bound by these Terms and any applicable policies, notices, or addenda we publish from time to time. If you do not accept these Terms, you must not use the Service.</p>
					<p class="mt-3">If you use the Service on behalf of a school, company, or other organization, you represent and warrant that you have authority to bind that entity. If you do not have that authority, you remain personally responsible for all obligations arising from your use of the Service.</p>
				</section>

				<section id="s2">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">2. Service Description</h2>
					<p>Edlink is a school management software-as-a-service platform that may include student registration and records, attendance, results, fee and finance management, homework, messaging, reporting, timetable, academic term management, and related administrative functions. Features may change, be withdrawn, or be added at our discretion unless otherwise agreed in writing.</p>
					<p class="mt-3">The Service is provided for institutional administration only. It is not designed for emergency response, medical decision-making, legal recordkeeping where offline originals are required, or any use case where uninterrupted availability is critical to safety, law, or compliance.</p>
				</section>

				<section id="s3">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">3. Accounts and Responsibility</h2>
					<ul class="list-disc pl-5 space-y-2">
						<li>You must provide true, complete, and current information when registering a school and creating user accounts.</li>
						<li>Each school operates as a separate tenant. Subject to your own configuration, your school's data is not intended to be visible to other schools using Edlink.</li>
						<li>You are responsible for all activity that occurs under your credentials or through accounts you create, approve, or administer.</li>
						<li>You must maintain reasonable administrative and technical safeguards for passwords, devices, and access permissions.</li>
						<li>Account roles control access. You are solely responsible for assigning, reviewing, suspending, and removing access in a timely manner.</li>
						<li>You are responsible for ensuring that your staff, users, and agents comply with these Terms and any school policies you apply through the Service.</li>
					</ul>
				</section>

				<section id="s4">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">4. Trials and Availability</h2>
					<ul class="list-disc pl-5 space-y-2">
						<li>Any trial, demo, pilot, or evaluation access is offered at our discretion and may be modified, suspended, or discontinued at any time.</li>
						<li>Unless we state otherwise in writing, trials are limited to one per school or organization and one per email address or other identifying information we reasonably use for abuse prevention.</li>
						<li>Trial access may be limited in features, storage, support, or duration.</li>
						<li>We may terminate trial access immediately if we believe the trial is being misused, abused, or used outside the permitted scope.</li>
						<li>When a trial ends, continued use of the Service requires activation of a paid plan or other written approval from Spotnet.</li>
					</ul>
				</section>

				<section id="s5">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">5. Fees and Payment</h2>
					<ul class="list-disc pl-5 space-y-2">
						<li>Paid plans are billed according to the pricing, billing cycle, and payment terms shown at the time of purchase or in your written agreement.</li>
						<li>All fees are due in advance unless we agree otherwise in writing.</li>
						<li>Fees are non-refundable except where mandatory law requires otherwise or where we expressly state a refund in writing.</li>
						<li>We may change pricing, packaging, or billing terms on reasonable notice, and continued use after the effective date constitutes acceptance.</li>
						<li>If payment is late, declined, reversed, or disputed without reasonable basis, we may suspend or restrict access, charge applicable taxes or recovery costs, and pursue collection to the extent permitted by law.</li>
						<li>Unless expressly stated otherwise, fees do not include taxes, levies, bank charges, mobile money charges, or foreign exchange costs, which remain your responsibility.</li>
					</ul>
				</section>

				<section id="s6">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">6. Student, Guardian and Staff Data</h2>
					<div class="rounded-xl border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 px-4 py-3 text-rose-700 dark:text-rose-300 text-xs font-medium mb-4">
						This section concerns student data, including data about children, and carries heightened legal responsibility.
					</div>
					<ul class="list-disc pl-5 space-y-2">
						<li>The school or organization using Edlink is the controller or equivalent responsible party for the personal data it uploads or directs us to process. Spotnet acts as a processor or service provider to the extent permitted by applicable law.</li>
						<li>You are solely responsible for obtaining and maintaining a lawful basis to collect, use, disclose, store, and transfer any personal data you submit to the Service, including any parent, guardian, staff, or student consent or notice required by law.</li>
						<li>You must not upload personal data unless you have the right and authority to do so and have met all applicable legal, regulatory, and contractual obligations.</li>
						<li>We may process personal data only as necessary to provide, secure, maintain, support, and improve the Service, to comply with law, and to enforce these Terms and our policies.</li>
						<li>Any privacy policy, data processing addendum, or similar document we publish or sign will apply in addition to these Terms, not in place of them.</li>
						<li>Data export and deletion requests are handled according to Section 8 and any applicable retention obligations.</li>
					</ul>
				</section>

				<section id="s7">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">7. Acceptable Use</h2>
					<p class="mb-2">You agree not to:</p>
					<ul class="list-disc pl-5 space-y-2">
						<li>Use the Service for any unlawful, fraudulent, harmful, harassing, discriminatory, or abusive purpose.</li>
						<li>Attempt to access, inspect, copy, or disclose another school's data or any data you are not authorized to access.</li>
						<li>Upload material that infringes third-party rights, violates law, or contains malicious code, spam, or unauthorized personal data.</li>
						<li>Reverse engineer, decompile, bypass, probe, disrupt, overload, scrape, or interfere with the Service or its security controls.</li>
						<li>Use automated tools, bots, or scripts except where we have expressly authorized them in writing.</li>
						<li>Use the Service to send unsolicited or unauthorized communications outside legitimate school administration.</li>
					</ul>
				</section>

				<section id="s8">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">8. Data Retention and Deletion</h2>
					<ul class="list-disc pl-5 space-y-2">
						<li>We retain data for as long as needed to provide the Service, meet legal obligations, resolve disputes, enforce agreements, and maintain backups or security logs.</li>
						<li>Trial, demo, or inactive account data may be deleted after the trial ends or after a reasonable inactivity period, without further notice, unless law requires longer retention.</li>
						<li>After termination or suspension, we may retain copies for a reasonable period for export, audit, legal, and operational purposes, after which we may delete or anonymize the data.</li>
						<li>We are not responsible for restoring deleted data unless a written support agreement expressly says otherwise.</li>
						<li>Data export requests may be subject to verification, scheduling, technical constraints, or reasonable administrative fees where permitted by law.</li>
					</ul>
				</section>

				<section id="s9">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">9. Service Availability</h2>
					<p>We use commercially reasonable efforts to operate and maintain the Service, but we do not guarantee uninterrupted, secure, error-free, or virus-free operation. The Service may be unavailable because of maintenance, upgrades, network failures, third-party systems, force majeure, or events outside our control. To the maximum extent permitted by law, we are not liable for losses caused by downtime, data loss, delayed processing, or service interruptions.</p>
				</section>

				<section id="s10">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">10. Intellectual Property</h2>
					<ul class="list-disc pl-5 space-y-2">
						<li>Edlink, including its software, design, text, graphics, logos, and other branding, is owned by Spotnet Technologies or its licensors and is protected by applicable intellectual property laws.</li>
						<li>Subject to these Terms, you retain ownership of the school content you submit, but you grant us a worldwide, non-exclusive, royalty-free license to host, process, transmit, display, reproduce, and otherwise use that content as reasonably necessary to operate, secure, support, and improve the Service.</li>
						<li>You may not copy, modify, distribute, resell, sublicense, or create derivative works of the Service except where permitted by law or by our written consent.</li>
						<li>Any feedback, suggestions, or improvement requests you provide may be used by us without restriction or payment to you.</li>
					</ul>
				</section>

				<section id="s11">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">11. Termination</h2>
					<ul class="list-disc pl-5 space-y-2">
						<li>You may cancel your subscription according to the plan terms or written agreement then in effect.</li>
						<li>We may suspend, restrict, or terminate access immediately if we believe you have breached these Terms, created a security risk, engaged in abuse, or exposed us or others to legal or operational risk.</li>
						<li>We may also suspend access for non-payment, suspected fraud, identity concerns, or if required by law or a lawful request.</li>
						<li>Termination does not waive rights or liabilities that arose before termination.</li>
						<li>Sections that by their nature should survive termination, including payment, IP, confidentiality, limitation of liability, indemnity, and governing law, will survive.</li>
					</ul>
				</section>

				<section id="s12">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">12. Limitation of Liability</h2>
					<p>To the maximum extent permitted by law, the Service is provided on an "as is" and "as available" basis without warranties of any kind, whether express, implied, statutory, or otherwise. Spotnet disclaims all implied warranties of merchantability, fitness for a particular purpose, non-infringement, and uninterrupted availability. To the maximum extent permitted by law, Spotnet will not be liable for indirect, incidental, special, consequential, exemplary, or punitive damages, or for lost profits, lost data, business interruption, or procurement of substitute services. Our total aggregate liability for any claim arising out of or relating to the Service will not exceed the fees actually paid to Spotnet by the affected school in the twelve (12) months before the event giving rise to the claim, or the minimum amount permitted by applicable law if a higher minimum applies.</p>
				</section>

				<section id="s13">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">13. Indemnification</h2>
					<p>You agree to defend, indemnify, and hold harmless Spotnet Technologies, its directors, officers, employees, contractors, and agents from and against any claims, losses, liabilities, damages, penalties, fines, costs, and expenses, including reasonable legal fees, arising from or related to: your use of the Service; your violation of these Terms; your violation of law; any content or data you submit; your infringement of third-party rights; or the acts or omissions of your users, staff, or representatives.</p>
				</section>

				<section id="s14">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">14. Changes to Terms</h2>
					<p>We may revise these Terms at any time by posting an updated version on the Site or by giving notice through the Service, by email, or by another reasonable method. The updated Terms take effect on the date stated in the revised version unless a later effective date is specified. Your continued use of the Service after the effective date means you accept the updated Terms. If you do not accept the changes, you must stop using the Service.</p>
				</section>

				<section id="s15">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">15. Governing Law</h2>
					<p>These Terms are governed by the laws of the Republic of Uganda, without regard to conflict of laws rules. Subject to any mandatory law to the contrary, the courts of Uganda will have exclusive jurisdiction over any dispute arising from or relating to the Service or these Terms.</p>
					<p class="mt-3">If any provision of these Terms is held unenforceable, the remaining provisions will continue in full force to the extent permitted by law. Our failure to enforce any provision is not a waiver of that provision.</p>
				</section>

				<section id="s16" class="pb-4">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">16. Contact</h2>
					<p>Questions about these Terms can be sent to <a href="mailto:contact@edlink.com" class="text-yellow-600 dark:text-yellow-400 font-medium">contact@edlink.com</a>. Notices to you may be sent to the email address associated with your account or through the Service.</p>
				</section>

			</div>
		</div>
	</div>

	<footer class="bg-darken text-gray-400">
		<div class="max-w-7xl mx-auto px-6 py-12 flex flex-col items-center text-center">
			<div class="flex items-center space-x-2 mb-4">
				<span class=" shrink-0">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
            <img src="{{ asset('img/logo.png') }}" alt="Edlink logo" class="w-[150px] h-auto">
            </a>
            </span>
                
			</div>
			<p class="text-xs text-gray-500">&copy; <span x-data x-text="new Date().getFullYear()"></span> Edlink. Built by Spotnet Technologies.</p>
		</div>
	</footer>

</body>
	<script type="module" src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js"></script>
</html>
