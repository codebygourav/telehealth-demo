import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

const authRoutes = ['/doctor/auth/login', '/doctor/auth/register', '/doctor/auth/forgot-password', '/login', '/register'];

export function proxy(request: NextRequest) {
    const token = request.cookies.get('doctor_token')?.value;
    const role = request.cookies.get('doctor_role')?.value;
    const path = request.nextUrl.pathname;

    const isAuthRoute = authRoutes.some(route => path.startsWith(route));

    // 🔒 1. If NOT logged in → redirect
    if (!token && !isAuthRoute) {
        return NextResponse.redirect(new URL('/doctor/auth/login', request.url));
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
            return NextResponse.redirect(new URL('/', request.url));
        }
    }

    return NextResponse.next();
}

export const config = {
    matcher: ['/((?!api|_next|favicon.ico|manifest.webmanifest|manifest.json|assets|icons).*)'],
};