<!DOCTYPE html>
<html lang="en" x-data="{ dark: localStorage.getItem('edlink_theme') === 'dark' || (!localStorage.getItem('edlink_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
      x-init="$watch('dark', v => { localStorage.setItem('edlink_theme', v ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', v); }); document.documentElement.classList.toggle('dark', dark);"
      :class="{ 'dark': dark }">
<head><link rel="icon" type="image/png" href="{{ asset('img/fav.png') }}"><link rel="apple-touch-icon" href="{{ asset('img/fav.png') }}">
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Privacy Policy - Edlink</title>
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
					<a href="#p1" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">1. Scope</a>
					<a href="#p2" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">2. Information We Collect</a>
					<a href="#p3" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">3. How We Use Information</a>
					<a href="#p4" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">4. Legal Bases and School Data</a>
					<a href="#p5" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">5. Sharing and Disclosure</a>
					<a href="#p6" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">6. Retention</a>
					<a href="#p7" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">7. Security</a>
					<a href="#p8" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">8. Children's Data</a>
					<a href="#p9" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">9. International Transfers</a>
					<a href="#p10" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">10. Your Rights</a>
					<a href="#p11" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">11. Cookies and Device Data</a>
					<a href="#p12" class="block text-gray-500 dark:text-gray-400 hover:text-yellow-500">12. Changes and Contact</a>
				</nav>
			</div>
		</aside>

		<div class="lg:col-span-3">
			<span class="text-xs font-bold tracking-widest text-yellow-500 uppercase">Legal</span>
			<h1 class="mt-3 text-3xl sm:text-4xl font-extrabold text-darken dark:text-white">Privacy Policy</h1>
			<p class="mt-3 text-gray-500 dark:text-gray-400">Last updated: {{ now()->format('d F Y') }}</p>

			<div class="mt-6 rounded-2xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-5 py-4 text-sm text-amber-800 dark:text-amber-300">
				This policy explains how Spotnet Technologies handles personal data in Edlink. Schools remain responsible for the lawfulness of the student, guardian, and staff data they enter into the Service.
			</div>

			<div class="prose-legal mt-10 space-y-10 text-sm leading-relaxed">

				<section id="p1">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">1. Scope</h2>
					<p>This Privacy Policy explains how Spotnet Technologies collects, uses, stores, discloses, and protects personal data when you use Edlink, visit our website, contact us, or otherwise interact with us. It applies to schools, staff, students, parents, guardians, and other users of the Service.</p>
					<p class="mt-3">If there is a conflict between this policy and a signed data processing addendum or other written agreement, the signed agreement will control to the extent of the conflict.</p>
				</section>

				<section id="p2">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">2. Information We Collect</h2>
					<p>We may collect the following categories of information:</p>
					<ul class="list-disc pl-5 space-y-2 mt-3">
						<li>Account and contact details such as names, email addresses, phone numbers, job titles, usernames, and login credentials.</li>
						<li>School and organization details such as school name, location, school number, class structure, staff lists, fee settings, and administrative records.</li>
						<li>Student, guardian, and staff data that schools or authorized users upload into Edlink, including registration details, attendance, academic records, messages, and related school content.</li>
						<li>Payment and billing information such as invoices, transaction references, subscription status, and payment confirmations.</li>
						<li>Usage and technical data such as IP address, browser type, device information, log data, time stamps, and interaction data generated when you use the Service.</li>
						<li>Support and communications data such as messages you send to us, feedback, complaints, and records of support interactions.</li>
					</ul>
				</section>

				<section id="p3">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">3. How We Use Information</h2>
					<p>We use personal data to operate and improve Edlink, create and manage accounts, process school records, provide support, send service notices, maintain security, troubleshoot errors, detect abuse, comply with legal obligations, and enforce our agreements.</p>
					<p class="mt-3">We may also use aggregated or de-identified information for analytics, product improvement, testing, reporting, and internal administration. Where data is de-identified, we will take reasonable steps to prevent re-identification.</p>
				</section>

				<section id="p4">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">4. Legal Bases and School Data</h2>
					<p>For school-uploaded data, the school or organization using Edlink is generally the controller or equivalent responsible party, and Spotnet acts as a processor or service provider to the extent permitted by law. The school is responsible for ensuring that it has a lawful basis to collect and share the data it enters into the Service, including any notice, consent, or other authorisation required by law.</p>
					<p class="mt-3">Where we process personal data for our own business purposes, we do so for legitimate business administration, performance of a contract, compliance with legal obligations, protection of our rights, and other lawful purposes permitted under applicable law.</p>
				</section>

				<section id="p5">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">5. Sharing and Disclosure</h2>
					<p>We do not sell personal data. We may share information only where reasonably necessary for the Service or where law requires it.</p>
					<ul class="list-disc pl-5 space-y-2 mt-3">
						<li>With service providers, hosting providers, payment providers, communication providers, and other vendors that help us operate Edlink under appropriate confidentiality and security obligations.</li>
						<li>With a school, to the extent that the school is entitled to receive the data or is using us as processor or service provider.</li>
						<li>With professional advisers, auditors, insurers, or advisors where needed for business, legal, or compliance purposes.</li>
						<li>To comply with law, lawful requests, court orders, regulatory demands, or to protect rights, safety, security, and property.</li>
						<li>In connection with a merger, acquisition, financing, reorganisation, or sale of assets, subject to appropriate protections.</li>
					</ul>
				</section>

				<section id="p6">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">6. Retention</h2>
					<p>We retain personal data only for as long as reasonably necessary for the purposes described in this policy, to provide the Service, to comply with legal obligations, to resolve disputes, to enforce agreements, and to maintain backups, archives, and security logs. Retention periods may vary depending on the type of data, the account status, and legal requirements.</p>
					<p class="mt-3">When data is no longer needed, we may delete it, anonymize it, or store it in a limited archived form where permitted by law or operational necessity.</p>
				</section>

				<section id="p7">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">7. Security</h2>
					<p>We use administrative, technical, and organisational safeguards designed to protect personal data against unauthorised access, alteration, loss, or disclosure. However, no system is completely secure, and we cannot guarantee absolute security. You are responsible for protecting your account credentials and for using the Service in a secure manner.</p>
				</section>

				<section id="p8">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">8. Children's Data</h2>
					<div class="rounded-xl border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 px-4 py-3 text-rose-700 dark:text-rose-300 text-xs font-medium mb-4">
						Edlink is used by schools and may contain information about children. That requires careful handling and strict school-side responsibility.
					</div>
					<p>Schools must ensure that they have the right to collect, use, and share student data before entering it into Edlink. Spotnet does not determine the purpose or lawful basis for the school's use of student data and relies on the school to provide lawful instructions.</p>
					<p class="mt-3">If we receive a request about student data that was uploaded by a school, we may direct the requester to the school first or coordinate with the school as appropriate and as required by law.</p>
				</section>

				<section id="p9">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">9. International Transfers</h2>
					<p>Because Edlink may use cloud infrastructure, support tools, or service providers located in different countries, personal data may be processed outside the country where it was collected. Where required by law, we will take reasonable steps to use appropriate safeguards for such transfers.</p>
				</section>

				<section id="p10">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">10. Your Rights</h2>
					<p>Depending on your role and applicable law, you may have rights to access, correct, update, delete, object to, restrict, or obtain a copy of your personal data, and to withdraw consent where consent is the legal basis. Some requests must be made through the school if the school controls the relevant data.</p>
					<p class="mt-3">We may need to verify your identity before responding to a request. We may also refuse or limit a request where the law permits or requires us to do so.</p>
				</section>

				<section id="p11">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">11. Cookies and Device Data</h2>
					<p>We may use cookies, session storage, local storage, and similar technologies to keep you signed in, remember preferences, maintain security, and understand how the Service is used. You can control some of these technologies through your browser settings, but disabling them may affect functionality.</p>
				</section>

				<section id="p12">
					<h2 class="text-xl font-bold text-darken dark:text-white mb-3">12. Changes and Contact</h2>
					<p>We may update this Privacy Policy from time to time. If we make material changes, we will post the updated version on this page or otherwise provide notice as required by law. Continued use of the Service after the updated policy becomes effective means you accept it.</p>
					<p class="mt-3">Questions or requests about this Privacy Policy can be sent to <a href="mailto:contact@edlink.com" class="text-yellow-600 dark:text-yellow-400 font-medium">contact@edlink.com</a>.</p>
				</section>

			</div>
		</div>
	</div>

	<footer class="bg-darken text-gray-400">
		<div class="max-w-7xl mx-auto px-6 py-12 flex flex-col items-center text-center">
			<div class="flex items-center space-x-2 mb-4">
				<a href="{{ url('/') }}" class="inline-flex items-center gap-2">
            <img src="{{ asset('img/logo.png') }}" alt="Edlink logo" class="w-[150px] h-auto">
            </a>
			</div>
			<p class="text-xs text-gray-500">&copy; <span x-data x-text="new Date().getFullYear()"></span> Edlink. Built by Spotnet Technologies.</p>
		</div>
	</footer>

</body>
	<script type="module" src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js"></script>
</html>
