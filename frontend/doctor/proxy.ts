import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

const authRoutes = ['/auth/login', '/auth/register', '/auth/forgot-password', '/login', '/register', '/doctor/auth/login', '/doctor/auth/register', '/doctor/auth/forgot-password'];

export function proxy(request: NextRequest) {
    const token = request.cookies.get('doctor_token')?.value;
    const role = request.cookies.get('doctor_role')?.value;
    const path = request.nextUrl.pathname;

    const isAuthRoute = authRoutes.some(route => path.startsWith(route));

    // 🔒 1. If NOT logged in → redirect
    if (!token && !isAuthRoute) {
        const loginPath = path.startsWith('/doctor') ? '/doctor/auth/login' : '/auth/login';
        return NextResponse.redirect(new URL(loginPath, request.url));
    }

    // 🔒 2. Doctor-only routes
    if (token && role !== 'doctor') {
        if (!isAuthRoute && path !== '/unauthorized') {
            return NextResponse.redirect(new URL('/unauthorized', request.url));
        }
    }

    // 🔁 3. Prevent logged-in users from accessing auth pages
    if (isAuthRoute && token && role) {
        if (role === 'doctor') {
            // After successful doctor login, keep them on the doctor dashboard
            return NextResponse.redirect(new URL('/doctor', request.url));
        }
        // For other roles (e.g., patient) keep original behavior
        return NextResponse.redirect(new URL('/', request.url));
    }

    return NextResponse.next();
}

export const config = {
    matcher: ['/((?!api|_next|favicon.ico|manifest.webmanifest|manifest.json|assets|icons).*)'],
};