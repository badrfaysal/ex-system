<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول — EFC Export</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    body { font-family: 'Cairo', sans-serif; }
    .gradient-bg { background: linear-gradient(135deg, #005B9F 0%, #008A3B 100%); }
    input:-webkit-autofill { -webkit-box-shadow: 0 0 0 30px white inset !important; }
</style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">

<div class="w-full max-w-md">

    {{-- بطاقة اللوجن --}}
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">

        {{-- هيدر --}}
        <div class="gradient-bg px-8 py-8 text-center text-white">
            <img src="{{ asset('images/EFC-.png') }}" alt="EFC" class="h-16 w-auto mx-auto mb-3 object-contain"
                 onerror="this.style.display='none'">
            <h1 class="text-xl font-black leading-tight">EFC Export</h1>
        </div>

        {{-- فورم --}}
        <div class="px-8 py-8">
            <h2 class="text-lg font-bold text-gray-800 mb-6 text-center">تسجيل الدخول</h2>

            @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-red-500 flex-shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        <i class="fas fa-envelope text-[#005B9F] text-xs me-1"></i>
                        البريد الإلكتروني
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        dir="ltr"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#005B9F] focus:ring-2 focus:ring-[#005B9F]/10 transition-all"
                        placeholder="example@email.com">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        <i class="fas fa-lock text-[#005B9F] text-xs me-1"></i>
                        كلمة المرور
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="passwordInput" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#005B9F] focus:ring-2 focus:ring-[#005B9F]/10 transition-all pe-10"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword()"
                            class="absolute top-1/2 -translate-y-1/2 end-3 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye text-sm" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="remember" id="remember" class="w-4 h-4 accent-[#005B9F] rounded">
                    <label for="remember" class="text-sm text-gray-600 cursor-pointer">تذكرني</label>
                </div>

                <button type="submit"
                    class="w-full py-3 bg-gradient-to-r from-[#005B9F] to-[#008A3B] text-white font-bold rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-sign-in-alt"></i>
                    دخول
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-white/60 text-xs mt-6">
        EFC Export &copy; {{ date('Y') }} — جميع الحقوق محفوظة
    </p>
</div>

<script>
function togglePassword() {
    const inp = document.getElementById('passwordInput');
    const ico = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'fas fa-eye-slash text-sm';
    } else {
        inp.type = 'password';
        ico.className = 'fas fa-eye text-sm';
    }
}
</script>
</body>
</html>
