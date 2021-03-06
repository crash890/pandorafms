// char_conv.hh
// This file is part of libpbe; see http://anyterm.org/
// (C) 2008 Philip Endecott

// Distributed under the Boost Software License, Version 1.0:
//
// Permission is hereby granted, free of charge, to any person or organization
// obtaining a copy of the software and accompanying documentation covered by
// this license (the "Software") to use, reproduce, display, distribute,
// execute, and transmit the Software, and to prepare derivative works of the
// Software, and to permit third-parties to whom the Software is furnished to
// do so, all subject to the following:
// 
// The copyright notices in the Software and this entire statement, including
// the above license grant, this restriction and the following disclaimer,
// must be included in all copies of the Software, in whole or in part, and
// all derivative works of the Software, unless such copies or derivative
// works are solely in the form of machine-executable object code generated by
// a source language processor.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE, TITLE AND NON-INFRINGEMENT. IN NO EVENT
// SHALL THE COPYRIGHT HOLDERS OR ANYONE DISTRIBUTING THE SOFTWARE BE LIABLE
// FOR ANY DAMAGES OR OTHER LIABILITY, WHETHER IN CONTRACT, TORT OR OTHERWISE,
// ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
// DEALINGS IN THE SOFTWARE.

#ifndef libpbe_charset_char_conv_hh
#define libpbe_charset_char_conv_hh

#include "charset_t.hh"
#include "charset_traits.hh"
#include "error_policy.hh"

#include <boost/static_assert.hpp>

#include "compiler_magic.hh"


namespace pbe {

// Conversion of characters between character sets
// -----------------------------------------------
//
// This file provides facilities to convert a single character from
// one character set to another.  It's implemented using specialisations
// for character set pairs in other files.
// A template parameter specifies a policy for error handling.
// Because function templates can't be partially specialised, and
// the character-set-pair specialisation will not want to be specialised
// on the error handling polocy, we need to wrap the conversion in a
// class.
// The default implementation here converts to a UCS4 character as an
// intermediate step, which will work as long as the necessary supporting
// conversions are implemented somehow.
// There's also a specialisation here for the null conversion where source
// and destination are the same.


// Base template, with default implementation via ucs4:

template <charset_t from_cs, charset_t to_cs, typename error_policy>
struct char_conv {
  // We shouldn't be here if from_cs or to_cs is ucs4; that indicates recursion.
  // A specialisation should have been invoked.  Maybe the appropriate
  // specialisation hasn't been #included, or hasn't been written?
  BOOST_STATIC_ASSERT(from_cs!=cs::ucs4 && to_cs!=cs::ucs4);

  // FIXME shouldn't this be a static member function?
  typename charset_traits<to_cs>::char_t 
  operator() ( typename charset_traits<from_cs>::char_t c,
               typename charset_traits<from_cs>::state_t& from_state
                 = charset_traits<from_cs>::state_t(),
               typename charset_traits<to_cs>::state_t& to_state
                 = charset_traits<to_cs>::state_t() )
  {
    charset_traits<cs::ucs4>::state_t ucs4_state;
    char32_t tmp = char_conv<from_cs, cs::ucs4, error_policy>() (c,from_state,ucs4_state);
    return         char_conv<cs::ucs4,   to_cs, error_policy>() (tmp,ucs4_state,to_state);
  }
};


// Specialisation for trivial conversion to the same character set:
template <charset_t cset, typename error_policy>
struct char_conv<cset,cset,error_policy> {
  typename charset_traits<cset>::char_t 
  operator() ( typename charset_traits<cset>::char_t c,
               PBE_UNUSED_ARG(typename charset_traits<cset>::state_t& from_state)
                 = charset_traits<cset>::state_t(),
               PBE_UNUSED_ARG(typename charset_traits<cset>::state_t& to_state)
                 = charset_traits<cset>::state_t() )
  {
    return c;
  }
};


// This macro is used elsewhere to define trivial conversions.

#define IDENTITY_CHAR_CONV(FROM_CS,TO_CS)                            \
template <typename error_policy>                                     \
struct char_conv<FROM_CS,TO_CS,error_policy> {                       \
  charset_traits<TO_CS>::char_t                                      \
  operator() ( charset_traits<FROM_CS>::char_t c,                    \
               charset_traits<FROM_CS>::state_t&,                    \
               charset_traits<TO_CS>::state_t& ) {                   \
    return static_cast<unsigned charset_traits<FROM_CS>::char_t>(c); \
  }                                                                  \
};


};

#endif
